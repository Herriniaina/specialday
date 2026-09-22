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
| Request DTO (use case input) | `src/Application/[Ctx]/` | `App\Application\[Ctx]` | `Application/User/RegisterUserRequest.php` |
| Application port (e.g. hasher, mailer) | `src/Application/[Ctx]/` | `App\Application\[Ctx]` | `Application/User/PasswordHasherInterface.php` |
| Application exception | `src/Application/[Ctx]/` | `App\Application\[Ctx]` | `Application/User/EmailAlreadyExistsException.php` |
| Doctrine **entity** | `src/Infrastructure/[Ctx]/Entity/` | `App\Infrastructure\[Ctx]\Entity` | `Infrastructure/User/Entity/User.php` |
| Repository **adapter** | `src/Infrastructure/[Ctx]/Repository/` | `App\Infrastructure\[Ctx]\Repository` | `Infrastructure/User/Repository/UserRepository.php` |
| Input DTO (HTTP payload, validated) | `src/Infrastructure/[Ctx]/Http/` | `App\Infrastructure\[Ctx]\Http` | `Infrastructure/User/Http/RegisterUserInput.php` |
| HTTP controller (API) | `src/Infrastructure/[Ctx]/Http/` | `App\Infrastructure\[Ctx]\Http` | `Infrastructure/User/Http/RegisterUserController.php` |
| Security adapter | `src/Infrastructure/[Ctx]/Security/` | `App\Infrastructure\[Ctx]\Security` | `Infrastructure/User/Security/SymfonyPasswordHasher.php` |
| Shared HTTP base / listeners | `src/Infrastructure/Shared/Http/` | `App\Infrastructure\Shared\Http` | `ApiAbstractController.php`, `EventListeners/…` |
| EasyAdmin CRUD / dashboard | `src/Infrastructure/Admin/Http/` | `App\Infrastructure\Admin\Http` | `Infrastructure/Admin/Http/CategoryCrudController.php` |

> `src/Controller/` still exists from the Symfony skeleton but is **empty and
> unused** — do not add controllers there.

---

## Reference files — read the one that matches what you're writing

This file only covers the shape of the project. Before writing code, read the
reference file for the layer you're touching — each has full explanations and
copy-pasteable examples:

| You're writing... | Read |
|---|---|
| Domain model, repository port, domain exception, use case, Request DTO | `references/domain-application.md` |
| Doctrine entity, repository adapter, EasyAdmin CRUD controller, a query | `references/infrastructure-persistence.md` |
| Input DTO, HTTP controller, or the tests for a new route | `references/infrastructure-http.md` |

`references/infrastructure-persistence.md` also documents when to add a class
to `phpunit.dist.xml`'s coverage `<exclude>` list — required reading whenever
you add a Doctrine entity, a skeleton repository, or an EasyAdmin controller.

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
2. **Public endpoints**: every API route reachable without authentication
   **must** live under the `/api/public/...` path prefix — never give an
   individual route its own `PUBLIC_ACCESS` entry. `config/packages/security.yaml`
   already grants `PUBLIC_ACCESS` to the whole prefix
   (`{ path: ^/api/public, roles: PUBLIC_ACCESS }`, placed **before** the
   `^/api` catch-all that requires `IS_AUTHENTICATED_FULLY`), so a new public
   controller only needs its `#[Route]` path to start with `/api/public/`
   (e.g. `/api/public/company/{companyId}/media`) — no security.yaml edit
   needed. This also applies to the JWT login endpoint itself
   (`/api/public/login`, wired through the `login` firewall's `pattern` and
   `json_login.check_path`, plus the `api_login` entry in
   `config/routes.yaml`). Everything else under `/api/...` is authenticated by
   default; only move a route out of `/api/public/` if it should require
   auth.
3. **Entity change**: create a migration in `migrations/`.
4. **Event listener**: needs an explicit service definition with
   `kernel.event_listener` tags in `config/services.yaml` (see
   `AcceptLanguageLocaleListener`).
5. **Tests**: every new route ships with its use-case test and Input DTO
   validation test — see `references/infrastructure-http.md`. A route without
   tests isn't finished.
6. **Coverage exclusions**: if the route introduced a pure-data Doctrine
   entity, a skeleton repository, or an EasyAdmin CRUD controller, add it to
   `phpunit.dist.xml`'s `<source><exclude>` — see the Coverage exclusions
   section in `references/infrastructure-persistence.md`.

---

## Naming conventions

- Use case: imperative verb + subject — `RegisterUser`, `CreateBooking`. Entry
  method `execute()`.
- Request DTO: `<UseCase>Request`, plain `readonly` data holder, no factory
  method, no framework attributes.
- Input DTO: `<UseCase>Input`, `readonly`, carries the Validator constraints and
  `#[SerializedName]` mapping, resolved via `#[MapRequestPayload]`.
- Port interface: `<Thing>RepositoryInterface`, `<Capability>Interface`
  (`PasswordHasherInterface`).
- Constructor-injected `<Thing>RepositoryInterface $x`: name `$x` after the
  interface with the `Interface` suffix dropped and lower-camelCased —
  `CompanyRepositoryInterface $companyRepository`,
  `SubscriptionRepositoryInterface $subscriptionRepository`. Never abbreviate
  or pluralize it (not `$companies`, not `$subscriptions`).
- Controller: `<Action><Ctx>Controller`, `final`, single `__invoke`.
- Doctrine entity vs domain model share a short name; alias on import
  (`use App\Infrastructure\User\Entity\User as UserEntity;`).
- IDs: UUID v7 everywhere, column type `uuid`, generator `UuidV7Generator`.
- DB columns: snake_case. PHP properties on DTOs/domain: camelCase.
