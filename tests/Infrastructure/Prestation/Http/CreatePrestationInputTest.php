<?php

namespace App\Tests\Infrastructure\Prestation\Http;

use App\Infrastructure\Prestation\Http\CreatePrestationInput;
use App\Infrastructure\Prestation\Http\PriceInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreatePrestationInputTest extends TestCase
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

    private function validInput(): CreatePrestationInput
    {
        return new CreatePrestationInput(
            prestationCategoryId: '01930f2e-6a3b-7c3e-9f3a-000000000001',
            price: new PriceInput(amount: 1000, currency: 'Ar', taxIncluded: true),
            quantity: 1,
            unit: 'photo',
        );
    }

    public function testAValidInputHasNoViolations(): void
    {
        self::assertCount(0, $this->validator->validate($this->validInput()));
    }

    public function testAValidInputWithNotesHasNoViolations(): void
    {
        $input = new CreatePrestationInput(
            prestationCategoryId: '01930f2e-6a3b-7c3e-9f3a-000000000001',
            price: new PriceInput(amount: 1000, currency: 'Ar', taxIncluded: true),
            quantity: 1,
            unit: 'photo',
            notes: 'Bring extra lenses.',
        );

        self::assertCount(0, $this->validator->validate($input));
    }

    public function testANullNotesIsAllowed(): void
    {
        $input = new CreatePrestationInput(
            prestationCategoryId: '01930f2e-6a3b-7c3e-9f3a-000000000001',
            price: new PriceInput(amount: 1000, currency: 'Ar', taxIncluded: true),
            quantity: 1,
            unit: 'photo',
            notes: null,
        );

        self::assertCount(0, $this->validator->validate($input));
    }

    public function testABlankPrestationCategoryIdIsRejected(): void
    {
        $input = new CreatePrestationInput(
            prestationCategoryId: '',
            price: new PriceInput(amount: 1000, currency: 'Ar', taxIncluded: true),
            quantity: 1,
            unit: 'photo',
        );

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('prestation.prestation_category_id.not_blank', $messages['prestationCategoryId'] ?? null);
    }

    public function testANonUuidPrestationCategoryIdIsRejected(): void
    {
        $input = new CreatePrestationInput(
            prestationCategoryId: 'not-a-uuid',
            price: new PriceInput(amount: 1000, currency: 'Ar', taxIncluded: true),
            quantity: 1,
            unit: 'photo',
        );

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('prestation.prestation_category_id.invalid', $messages['prestationCategoryId'] ?? null);
    }

    public function testAMissingQuantityIsRejected(): void
    {
        $input = new CreatePrestationInput(
            prestationCategoryId: '01930f2e-6a3b-7c3e-9f3a-000000000001',
            price: new PriceInput(amount: 1000, currency: 'Ar', taxIncluded: true),
            quantity: null,
            unit: 'photo',
        );

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('prestation.quantity.not_blank', $messages['quantity'] ?? null);
    }

    public function testAZeroQuantityIsRejected(): void
    {
        $input = new CreatePrestationInput(
            prestationCategoryId: '01930f2e-6a3b-7c3e-9f3a-000000000001',
            price: new PriceInput(amount: 1000, currency: 'Ar', taxIncluded: true),
            quantity: 0,
            unit: 'photo',
        );

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('prestation.quantity.positive', $messages['quantity'] ?? null);
    }

    public function testABlankUnitIsRejected(): void
    {
        $input = new CreatePrestationInput(
            prestationCategoryId: '01930f2e-6a3b-7c3e-9f3a-000000000001',
            price: new PriceInput(amount: 1000, currency: 'Ar', taxIncluded: true),
            quantity: 1,
            unit: '',
        );

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('prestation.unit.not_blank', $messages['unit'] ?? null);
    }

    public function testAUnitLongerThan50CharactersIsRejected(): void
    {
        $input = new CreatePrestationInput(
            prestationCategoryId: '01930f2e-6a3b-7c3e-9f3a-000000000001',
            price: new PriceInput(amount: 1000, currency: 'Ar', taxIncluded: true),
            quantity: 1,
            unit: str_repeat('a', 51),
        );

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('prestation.unit.too_long', $messages['unit'] ?? null);
    }

    public function testAMissingPriceAmountIsRejected(): void
    {
        $input = new CreatePrestationInput(
            prestationCategoryId: '01930f2e-6a3b-7c3e-9f3a-000000000001',
            price: new PriceInput(amount: null, currency: 'Ar', taxIncluded: true),
            quantity: 1,
            unit: 'photo',
        );

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('prestation.price.amount.not_blank', $messages['price.amount'] ?? null);
    }

    public function testANegativePriceAmountIsRejected(): void
    {
        $input = new CreatePrestationInput(
            prestationCategoryId: '01930f2e-6a3b-7c3e-9f3a-000000000001',
            price: new PriceInput(amount: -1, currency: 'Ar', taxIncluded: true),
            quantity: 1,
            unit: 'photo',
        );

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('prestation.price.amount.positive_or_zero', $messages['price.amount'] ?? null);
    }

    public function testABlankPriceCurrencyIsRejected(): void
    {
        $input = new CreatePrestationInput(
            prestationCategoryId: '01930f2e-6a3b-7c3e-9f3a-000000000001',
            price: new PriceInput(amount: 1000, currency: '', taxIncluded: true),
            quantity: 1,
            unit: 'photo',
        );

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('prestation.price.currency.not_blank', $messages['price.currency'] ?? null);
    }

    public function testAPriceCurrencyLongerThan10CharactersIsRejected(): void
    {
        $input = new CreatePrestationInput(
            prestationCategoryId: '01930f2e-6a3b-7c3e-9f3a-000000000001',
            price: new PriceInput(amount: 1000, currency: str_repeat('a', 11), taxIncluded: true),
            quantity: 1,
            unit: 'photo',
        );

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('prestation.price.currency.too_long', $messages['price.currency'] ?? null);
    }

    public function testAMissingPriceTaxIncludedIsRejected(): void
    {
        $input = new CreatePrestationInput(
            prestationCategoryId: '01930f2e-6a3b-7c3e-9f3a-000000000001',
            price: new PriceInput(amount: 1000, currency: 'Ar', taxIncluded: null),
            quantity: 1,
            unit: 'photo',
        );

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('prestation.price.tax_included.not_blank', $messages['price.taxIncluded'] ?? null);
    }
}
