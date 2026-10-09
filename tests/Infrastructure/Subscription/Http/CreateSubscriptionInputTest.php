<?php

namespace App\Tests\Infrastructure\Subscription\Http;

use App\Infrastructure\Subscription\Http\CreateSubscriptionInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateSubscriptionInputTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    /**
     * @return array<string, string>
     */
    private function messagesByPropertyPath(ConstraintViolationListInterface $violations): array
    {
        $messages = [];
        foreach ($violations as $violation) {
            $messages[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $messages;
    }

    public function testAValidInputHasNoViolations(): void
    {
        $input = new CreateSubscriptionInput(
            plan: 'standard',
            paymentMethod: 'mobile_money',
        );

        self::assertCount(0, $this->validator->validate($input));
    }

    public function testDefaultsAreValid(): void
    {
        $input = new CreateSubscriptionInput();

        self::assertSame('free', $input->plan);
        self::assertNull($input->paymentMethod);
        self::assertCount(0, $this->validator->validate($input));
    }

    public function testAnInvalidPlanIsRejected(): void
    {
        $input = new CreateSubscriptionInput(plan: 'gold');

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('subscription.plan.invalid', $messages['plan'] ?? null);
    }

    public function testAnInvalidPaymentMethodIsRejected(): void
    {
        $input = new CreateSubscriptionInput(paymentMethod: 'cash');

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('subscription.payment_method.invalid', $messages['paymentMethod'] ?? null);
    }
}
