---
name: architecture
description: >-
  Read this BEFORE creating or modifying any class under src/ in this project —
  Controller, Doctrine Entity, domain model, DTO / Request object, Application use
  case, Repository, port interface, event listener, or EasyAdmin CRUD controller.
  Explains the hexagonal / ports-and-adapters layout: pure-PHP Domain,
  framework-free Application use cases, and per-context Infrastructure adapters
  (Entity, Repository, Http, Security). Use whenever the user asks to "add a
  controller", "create an entity", "new API endpoint", "add a use case / DTO",
  "add a repository", or anything that adds/edits code in src/.
---

# Project architecture — hexagonal (ports & adapters)

Everything application code lives in `src/`, PSR-4 root `App\` → `src/`
(`composer.json`). Three layers, each split by **bounded context** (`User`,
`Category`,…).

| Layer | Path | May depend on | MUST NOT import |
|---|---|---|---|
| **Domain** | `src/Domain/[Context]/` | Pure PHP only. | Symfony, Doctrine, EasyAdmin, any framework/ORM |
| **Application** | `src/Application/[Context]/` | Domain | Pure php only. |
| **Infrastructure** | `src/Infrastructure/[Context]/` | Application + Domain + any framework | — (this is the only place framework code is allowed) |

Dependencies point **inward only**: Infrastructure → Application → Domain. Domain
knows nothing about the outside.

---

## Golden rules

1. **Domain and Application use case classes contain zero framework imports.** No
   `use Symfony\...`, no `use Doctrine\...`, no attributes from those namespaces.
2. Interaction with the outside world goes through a **port** (an interface owned
   by Domain or Application) that Infrastructure implements as an **adapter**.
3. There are **two `User` classes on purpose**: the domain model
   `App\Domain\User\User` (pure) and the Doctrine entity
   `App\Infrastructure\User\Entity\User`. The repository adapter maps between
   them. Never make the domain model a Doctrine entity.
4. A new port needs a wiring alias in `config/services.yaml` (see Wiring below).

---

## Where each artifact goes

| Artifact | Location | Namespace | Example |
|---|---|---|---|
| Domain model / aggregate | `src/Domain/[Ctx]/` | `App\Domain\[Ctx]` | `Domain/User/User.php` |
| Repository **port** (interface) | `src/Domain/[Ctx]/` | `App\Domain\[Ctx]` | `Domain/User/UserRepositoryInterface.php` |
| Domain enum / exception | `src/Domain/[Ctx]/` | `App\Domain\[Ctx]` | `Domain/User/UserRoleEnum.php`, `UserNotFoundException.php` |
| Use case (application service) | `src/Application/[Ctx]/` | `App\Application\[Ctx]` | `Application/User/RegisterUser.php` |
| Request / input DTO | `src/Application/[Ctx]/` | `App\Application\[Ctx]` | `Application/User/RegisterUserRequest.php` |
| Application port (e.g. hasher, mailer) | `src/Application/[Ctx]/` | `App\Application\[Ctx]` | `Application/User/PasswordHasherInterface.php` |
| Application exception | `src/Application/[Ctx]/` | `App\Application\[Ctx]` | `Application/User/EmailAlreadyExistsException.php` |
| Doctrine **entity** | `src/Infrastructure/[Ctx]/Entity/` | `App\Infrastructure\[Ctx]\Entity` | `Infrastructure/User/Entity/User.php` |
| Repository **adapter** | `src/Infrastructure/[Ctx]/Repository/` | `App\Infrastructure\[Ctx]\Repository` | `Infrastructure/User/Repository/UserRepository.php` |
| HTTP controller (API) | `src/Infrastructure/[Ctx]/Http/` | `App\Infrastructure\[Ctx]\Http` | `Infrastructure/User/Http/RegisterUserController.php` |
| Security adapter | `src/Infrastructure/[Ctx]/Security/` | `App\Infrastructure\[Ctx]\Security` | `Infrastructure/User/Security/SymfonyPasswordHasher.php` |
| Shared HTTP base / listeners | `src/Infrastructure/Shared/Http/` | `App\Infrastructure\Shared\Http` | `ApiAbstractController.php`, `EventListeners/…` |
| EasyAdmin CRUD / dashboard | `src/Infrastructure/Admin/Http/` | `App\Infrastructure\Admin\Http` | `Infrastructure/Admin/Http/CategoryCrudController.php` |

> `src/Controller/` still exists from the Symfony skeleton but is **empty and
> unused** — do not add controllers there.

---

## Domain layer

Pure PHP. Model with promoted constructor properties; identity kept private and
exposed as a primitive.

```php
<?php

namespace App\Domain\Booking;

class Booking
{
    private ?string $id = null;

    public function __construct(
        public string $reference,
        public \DateTimeImmutable $date,
    ) {}

    public function getId(): ?string
    {
        return $this->id?->toString();
    }
}
```

Repository port — interface phrased in **domain terms**, takes/returns domain
models, never Doctrine types:

```php
<?php

namespace App\Domain\Booking;

interface BookingRepositoryInterface
{
    public function save(Booking $booking): void;

    public function findByReference(string $reference): ?Booking;
}
```

Domain exceptions extend SPL exceptions (`\RuntimeException`,
`\InvalidArgumentException`), no framework base classes.

---

## Application layer

### Use case class

One class per use case, named after the action. Single public entry point
`execute(...)`. Depends only on ports + domain. **No framework imports.**

```php
<?php

namespace App\Application\Booking;

use App\Domain\Booking\Booking;
use App\Domain\Booking\BookingRepositoryInterface;

class CreateBooking
{
    public function __construct(
        private BookingRepositoryInterface $bookings,
    ) {}

    public function execute(CreateBookingRequest $request): void
    {
        if ($this->bookings->findByReference($request->reference) !== null) {
            throw new BookingAlreadyExistsException('Reference already used.');
        }

        $this->bookings->save(new Booking(
            $request->reference,
            new \DateTimeImmutable($request->date),
        ));
    }
}
```

### Request / input DTO

`readonly` class, promoted properties, plus a `fromArray()` factory that maps the
snake_case JSON payload and supplies defaults.

```php
<?php

namespace App\Application\Booking;

readonly class CreateBookingRequest
{
    public function __construct(
        public string $reference,
        public string $date,
    ) {}

    public static function fromArray(array $payload): self
    {
        return new self(
            reference: $payload['reference'] ?? '',
            date: $payload['date'] ?? '',
        );
    }
}
```

> **Validation note.** The intended rule is that Application code is framework-free.
> The existing `RegisterUserRequest` currently carries
> `Symfony\Component\Validator\Constraints` attributes — treat that as a legacy
> deviation, not the pattern to copy. Prefer keeping DTOs annotation-free and
> running validation at the HTTP boundary (controller / Doctrine entity). If you
> must match the existing style for consistency in the `User` context, keep it
> confined to the DTO and never let it leak into a use case class.

---

## Infrastructure layer

### Doctrine entity — `Infrastructure/[Ctx]/Entity/`

UUID v7 identity with the custom generator, snake_case column names, fluent
setters returning `static`.

```php
<?php

namespace App\Infrastructure\Booking\Entity;

use App\Infrastructure\Booking\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private ?UuidInterface $id = null;

    #[ORM\Column(length: 255)]
    private ?string $reference = null;

    public function getId(): ?UuidInterface { return $this->id; }
    public function getReference(): ?string { return $this->reference; }
    public function setReference(string $reference): static { $this->reference = $reference; return $this; }
}
```

After adding/changing an entity: generate and review a migration in `migrations/`
(`bin/console doctrine:migrations:diff` inside the container).

### Repository adapter — `Infrastructure/[Ctx]/Repository/`

**Preferred style — implements the domain port**, injects
`EntityManagerInterface`, maps domain model ↔ entity, marks overrides:

```php
<?php

namespace App\Infrastructure\Booking\Repository;

use App\Domain\Booking\Booking;
use App\Domain\Booking\BookingRepositoryInterface;
use App\Infrastructure\Booking\Entity\Booking as BookingEntity;
use Doctrine\ORM\EntityManagerInterface;
use Override;

class BookingRepository implements BookingRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Override]
    public function save(Booking $booking): void
    {
        $entity = (new BookingEntity())->setReference($booking->reference);
        $this->em->persist($entity);
        $this->em->flush();
    }

    #[Override]
    public function findByReference(string $reference): ?Booking
    {
        $entity = $this->em->getRepository(BookingEntity::class)
            ->findOneBy(['reference' => $reference]);

        return $entity === null
            ? null
            : new Booking($entity->getReference(), /* … */);
    }
}
```

**Skeleton style — `extends ServiceEntityRepository`** (as in `CategoryRepository`):
only for contexts with **no domain port**, typically admin/CRUD-only entities.

### HTTP controller (API) — `Infrastructure/[Ctx]/Http/`

`final` single-action `__invoke` controller, `extends ApiAbstractController`
(`src/Infrastructure/Shared/Http/ApiAbstractController.php` — gives `success()`,
`created()`, `badRequest()`, `notFound()`, `conflict()`, `error()`). Flow:
parse body → build Request DTO → validate → call use case → map exceptions to
responses.

```php
<?php

namespace App\Infrastructure\Booking\Http;

use App\Application\Booking\BookingAlreadyExistsException;
use App\Application\Booking\CreateBooking;
use App\Application\Booking\CreateBookingRequest;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Exception;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateBookingController extends ApiAbstractController
{
    public function __construct(
        private CreateBooking $createBooking,
        private ValidatorInterface $validator,
    ) {}

    #[Route('/api/booking', name: 'app_booking_create', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = CreateBookingRequest::fromArray($request->toArray());

            $violations = $this->validator->validate($dto);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $v) {
                    $errors[$v->getPropertyPath()] = $v->getMessage();
                }

                return $this->badRequest('Invalid parameter', $errors);
            }

            $this->createBooking->execute($dto);
        } catch (JsonException) {
            return $this->badRequest('Invalid json format.');
        } catch (BookingAlreadyExistsException $e) {
            return $this->conflict($e->getMessage());
        } catch (Exception) {
            return $this->error();
        }

        return $this->created();
    }
}
```

Routes are attribute-based and auto-imported (`config/routes.yaml` →
`routing.controllers`). No YAML route entry needed.

### EasyAdmin CRUD — `Infrastructure/Admin/Http/`

`extends AbstractCrudController`, `getEntityFqcn()` returns the **Doctrine
entity** FQCN, fields configured in `configureFields()`. Register it in
`DashboardController` menu. EasyAdmin routes are auto-generated
(`config/routes/easyadmin.yaml`).

---

## Wiring checklist (do this when adding an adapter/endpoint)

1. **Port → adapter alias** in `config/services.yaml` under `services:` — required
   for every Domain/Application interface, e.g.:
   ```yaml
   App\Application\User\PasswordHasherInterface:
       alias: App\Infrastructure\User\Security\SymfonyPasswordHasher
   ```
   Add the same for each new `*RepositoryInterface`
   (`App\Domain\Booking\BookingRepositoryInterface: '@App\Infrastructure\Booking\Repository\BookingRepository'`).
   Everything in `src/` is otherwise auto-registered by the `App\:` resource
   loader with `autowire`/`autoconfigure` on.
2. **Public endpoints**: add an `access_control` line in
   `config/packages/security.yaml` (new API paths are behind
   `IS_AUTHENTICATED_FULLY` by default; register-style public routes need an
   explicit `PUBLIC_ACCESS` entry, placed **before** the `^/api` catch-all).
3. **Entity change**: create a migration in `migrations/`.
4. **Event listener**: needs an explicit service definition with
   `kernel.event_listener` tags in `config/services.yaml` (see
   `AcceptLanguageLocaleListener`).

---

## Naming conventions

- Use case: imperative verb + subject — `RegisterUser`, `CreateBooking`. Entry
  method `execute()`.
- Request DTO: `<UseCase>Request` with static `fromArray(array): self`.
- Port interface: `<Thing>RepositoryInterface`, `<Capability>Interface`
  (`PasswordHasherInterface`).
- Controller: `<Action><Ctx>Controller`, `final`, single `__invoke`.
- Doctrine entity vs domain model share a short name; alias on import
  (`use App\Infrastructure\User\Entity\User as UserEntity;`).
- IDs: UUID v7 everywhere, column type `uuid`, generator `UuidV7Generator`.
- DB columns: snake_case. PHP properties on DTOs/domain: camelCase.