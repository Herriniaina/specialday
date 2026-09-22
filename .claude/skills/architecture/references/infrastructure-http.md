# Infrastructure — HTTP (Input DTO, controller, testing)

## Input DTO — `Infrastructure/[Ctx]/Http/`

One `readonly` class per endpoint, resolved by Symfony's
`#[MapRequestPayload]` (handles JSON parsing, deserialization **and**
validation before the controller body runs — no manual `try`/`catch` for JSON,
no manual `ValidatorInterface` call). This is the **only** place request-shape
validation lives: `Symfony\Component\Validator\Constraints` attributes and
`#[SerializedName]` for snake_case → camelCase mapping. Give every property a
default so a missing field fails validation instead of throwing during
deserialization.

```php
<?php

namespace App\Infrastructure\Booking\Http;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateBookingInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'booking.reference.not_blank')]
        public string $reference = '',

        #[SerializedName('date')]
        #[Assert\NotBlank(message: 'booking.date.not_blank')]
        public string $date = '',
    ) {
    }
}
```

## HTTP controller (API) — `Infrastructure/[Ctx]/Http/`

`final` single-action `__invoke` controller, `extends ApiAbstractController`
(`src/Infrastructure/Shared/Http/ApiAbstractController.php` — gives `success()`,
`created()`, `badRequest()`, `notFound()`, `conflict()`, `error()`). Flow:
Symfony resolves + validates the Input DTO → controller maps it field-by-field
into a Request DTO → call use case → map exceptions to responses.

```php
<?php

namespace App\Infrastructure\Booking\Http;

use App\Application\Booking\BookingAlreadyExistsException;
use App\Application\Booking\CreateBooking;
use App\Application\Booking\CreateBookingRequest;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CreateBookingController extends ApiAbstractController
{
    public function __construct(
        private CreateBooking $createBooking,
    ) {}

    #[Route('/api/booking', name: 'app_booking_create', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] CreateBookingInput $input): JsonResponse
    {
        try {
            $this->createBooking->execute(new CreateBookingRequest(
                reference: $input->reference,
                date: new \DateTimeImmutable($input->date),
            ));
        } catch (BookingAlreadyExistsException $e) {
            return $this->conflict($e->getMessage());
        } catch (Exception) {
            return $this->error();
        }

        return $this->created();
    }
}
```

`#[MapRequestPayload]` already returns a 422 with violation details when the
Input DTO fails validation, so the controller body only runs on valid input —
no manual violation-collecting loop needed. Routes are attribute-based and
auto-imported (`config/routes.yaml` → `routing.controllers`). No YAML route
entry needed.

## Testing — every new route needs unit tests

A new endpoint is not done until it has tests. This codebase has no
`WebTestCase`/functional HTTP tests — coverage comes from two unit-test
files per route, mirroring `tests/Application/[Ctx]/` and
`tests/Infrastructure/[Ctx]/Http/` (see `CreateCompanyTest`/
`CreateCompanyInputTest`, `ListCompanyMediaFilesTest`, or
`CreateSubscriptionTest`/`CreateSubscriptionInputTest`):

1. **Use case test** — `tests/Application/[Ctx]/<UseCase>Test.php`, plain
   `TestCase`. Mock every port the use case depends on with
   `createMock(...)` (assert on calls with `expects(self::once())->method(...)
   ->with(...)`) or `createStub(...)` for ports whose calls you don't need to
   assert (see the `&MockObject` / `&Stub` property types in existing tests).
   Cover: the happy path (correct data passed to `save`/`create`, correct
   return value), and **every exception the use case can throw** — one test
   per guard clause (not-found, already-exists, etc.), asserting the
   short-circuited port method (e.g. `save`/`create`) is `expects(self::never())`.
   PHPUnit's mock-expectation check is strict here: a `MockObject` stubbed
   with only `->method(...)->willReturn(...)` and no `expects(...)` on any of
   its methods raises a `PHPUnit Notice` ("No expectations were configured
   for the mock") — always call it through `expects(self::once())` (or use
   `createStub` if the test genuinely doesn't care how many times/with what
   args it's called).
2. **Input DTO validation test** — `tests/Infrastructure/[Ctx]/Http/<Input>Test.php`,
   build a real `ValidatorInterface` via
   `Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()`
   (no HTTP kernel needed). Cover: a fully valid input has zero violations,
   defaulted/optional fields are valid when omitted, and each constraint
   (`NotBlank`, `Uuid`, `Choice`, `Length`, …) is individually violated by a
   bad value, asserting the violation's `message` matches the translation key
   set on the attribute.

Run `docker compose exec php php bin/phpunit <path>` (project has no bare
`php`/`vendor/bin/phpunit` on the host — always go through the `php`
container) and `docker compose exec php vendor/bin/phpstan analyse` before
considering the route finished.
