<?php

namespace App\Tests\Infrastructure\User\Http;

use App\Infrastructure\User\Http\ChangePasswordInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ChangePasswordInputTest extends TestCase
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
    private function validInput(array $overrides = []): ChangePasswordInput
    {
        $defaults = [
            'currentPassword' => 'current-password',
            'newPassword' => 'new-password123',
        ];
        $args = array_replace($defaults, $overrides);

        return new ChangePasswordInput(...$args);
    }

    public function testAValidInputHasNoViolations(): void
    {
        self::assertCount(0, $this->validator->validate($this->validInput()));
    }

    public function testABlankCurrentPasswordIsRejected(): void
    {
        $violations = $this->validator->validate($this->validInput(['currentPassword' => '']));

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertContains('user.current_password.not_blank', $messages);
    }

    public function testABlankNewPasswordIsRejected(): void
    {
        $violations = $this->validator->validate($this->validInput(['newPassword' => '']));

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertContains('user.new_password.not_blank', $messages);
    }

    public function testANewPasswordShorterThanEightCharactersIsRejected(): void
    {
        $violations = $this->validator->validate($this->validInput(['newPassword' => 'short']));

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertContains('user.new_password.too_short', $messages);
    }
}
