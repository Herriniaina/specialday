<?php

namespace App\Tests\Infrastructure\User\Http;

use App\Infrastructure\User\Http\RegisterUserInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterUserInputTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function validInput(array $overrides = []): RegisterUserInput
    {
        $defaults = [
            'email' => 'jane@example.com',
            'password' => 'password123',
            'roles' => ['ROLE_COMPANY_ADMIN'],
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'phoneNumber' => '+261340000000',
        ];
        $args = array_replace($defaults, $overrides);

        return new RegisterUserInput(...$args);
    }

    public function testAValidInputHasNoViolations(): void
    {
        self::assertCount(0, $this->validator->validate($this->validInput()));
    }

    public function testABlankEmailIsRejected(): void
    {
        $violations = $this->validator->validate($this->validInput(['email' => '']));

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertContains('user.email.not_blank', $messages);
    }

    public function testAnInvalidEmailIsRejected(): void
    {
        $violations = $this->validator->validate($this->validInput(['email' => 'not-an-email']));

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertContains('user.email.invalid', $messages);
    }

    public function testAPasswordShorterThanEightCharactersIsRejected(): void
    {
        $violations = $this->validator->validate($this->validInput(['password' => 'short']));

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertContains('user.password.too_short', $messages);
    }

    public function testAnUnknownRoleIsRejected(): void
    {
        $violations = $this->validator->validate($this->validInput(['roles' => ['ROLE_NOT_A_ROLE']]));

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertContains('user.roles.invalid', $messages);
    }

    public function testAPlatformRoleCannotBeRequestedOnSignUp(): void
    {
        $violations = $this->validator->validate($this->validInput(['roles' => ['ROLE_COMPANY_ADMIN', 'ROLE_ADMIN']]));

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertContains('user.roles.invalid', $messages);
    }

    public function testAnInvalidPhoneNumberIsRejected(): void
    {
        $violations = $this->validator->validate($this->validInput(['phoneNumber' => 'not-a-phone']));

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertContains('user.phone.invalid', $messages);
    }
}
