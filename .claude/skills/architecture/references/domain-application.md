# Domain and Application layers

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

### Request DTO

`readonly` class, promoted properties only. No factory method, no defaults logic,
**no framework attributes** — the HTTP boundary (the Input DTO, in
`references/infrastructure-http.md`) is responsible for parsing and validating;
by the time a use case sees a Request it is already trusted data.

```php
<?php

namespace App\Application\Booking;

readonly class CreateBookingRequest
{
    public function __construct(
        public string $reference,
        public \DateTimeImmutable $date,
    ) {}
}
```
