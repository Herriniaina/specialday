# Infrastructure — persistence (entity, repository, EasyAdmin)

## Doctrine entity — `Infrastructure/[Ctx]/Entity/`

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

## Repository adapter — `Infrastructure/[Ctx]/Repository/`

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

### Querying — avoid N+1, select the minimum fields

For read paths returning **many rows** (lists, exports, reports), never
`select('s')` a full entity when the mapper only reads a few of its fields —
that costs the same as `SELECT *`, multiplied by every row. Select just the
columns the domain model needs, hydrate as an array, and build the domain
object straight from the row (skip the entity-accessor round-trip):

```php
use Doctrine\ORM\AbstractQuery;

$row = $this->em->createQueryBuilder()
    ->select('s.id AS id', 's.storageUsedBytes AS storageUsedBytes', 'sp.storage AS storageQuotaBytes')
    ->from(SubscriptionEntity::class, 's')
    ->join('s.subscriptionPlan', 'sp')
    ->where('s.company = :companyId')
    ->setParameter('companyId', $companyId)
    ->getQuery()
    ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);
```

Use `IDENTITY(s.assoc)` to pull a to-one association's raw FK instead of
joining just to read its id.

For a **single-row lookup** (e.g. `findByCompanyId` returning one nullable
domain object), the column-count/network savings above are negligible —
selecting the full entity (+ its joined to-one associations) and mapping via
its typed getters is fine, and PHPStan-friendlier than an array shape. See
`SubscriptionRepository::findByCompanyId` for the pattern: `select('s', 'sp')`,
object hydration, `assert($entity instanceof …)` to narrow past
`getOneOrNullResult()`'s `mixed` return type, then map into the domain object.
**Always `$this->em->detach($entity)` before returning** in this case — the
entity would otherwise stay in the `EntityManager`'s identity map as
*managed*. That's harmless in a short-lived HTTP request, but this app also
runs a Messenger worker (long-lived process): a lingering managed entity
grows worker memory over the process lifetime and can get written by an
unrelated `flush()` elsewhere in the same message if something mutates it.
Detaching keeps a read-only lookup side-effect-free.

None of this applies to a write path that needs to mutate and `flush()` an
entity (e.g. an increment method) — there you need the real, fully-hydrated,
**managed** entity so Doctrine's change tracking and lifecycle callbacks
fire; full hydration (without detaching) there is correct, not a violation
of this rule.

Watch for N+1: touching a lazy `ManyToOne`/`OneToOne` field other than its
identifier (`getId()` is free — Doctrine already knows it from the FK column
present on the owning row) triggers one query per access. In a loop over
many rows this becomes one query per row. Either join the association
eagerly (as above) or avoid touching anything but `getId()` on it.

## EasyAdmin CRUD — `Infrastructure/Admin/Http/`

`extends AbstractCrudController`, `getEntityFqcn()` returns the **Doctrine
entity** FQCN, fields configured in `configureFields()`. Register it in
`DashboardController` menu. EasyAdmin routes are auto-generated
(`config/routes/easyadmin.yaml`). This directory is excluded from the
test-coverage metric — see Coverage exclusions below.

## Coverage exclusions — `phpunit.dist.xml`

CI fails a PR if test coverage drops below the last known baseline (see
`.github/workflows/ci.yaml`). That check is only meaningful if the
denominator only counts code that's actually worth unit-testing. Every time
you add a class in one of the categories below, add it to the `<exclude>`
block inside `<source>` in `phpunit.dist.xml` **in the same PR** — don't
leave it to drag the percentage down and get caught by the regression check
later:

- **Doctrine entity that's a pure data holder** — only ID/column properties
  with plain getters and fluent setters, no conditional logic, no validation,
  no computed values. Exclude by `<file>`. (Counter-example: `Infrastructure/
  User/Entity/User.php` stays **covered** — `getUserIdentifier()`,
  `getRoles()`, `setRoles()` all carry real branching/validation, so it's
  tested, not excluded. If a new entity grows this kind of logic later,
  remove its exclusion rather than leaving it stale.)
- **New EasyAdmin CRUD/dashboard controller** under `Infrastructure/Admin/
  Http/` — declarative field config and page titles only. If a whole new
  directory is added under `Infrastructure/Admin`, exclude the `<directory>`
  instead of listing files one by one (see the existing `src/Infrastructure/
  Admin` exclusion).
- **Skeleton-style repository** (`extends ServiceEntityRepository`, no
  methods beyond the constructor — the "no domain port" case above). Exclude
  by `<file>`. The moment it gains a custom method, remove the exclusion — it
  now has logic worth testing.

Do **not** exclude: HTTP controllers, Input DTOs, use cases, repositories
that implement a domain port (even thin-looking ones — they map
domain↔entity, which is exactly the kind of logic worth testing), or any
class with a conditional, a loop, or a thrown exception in it.
