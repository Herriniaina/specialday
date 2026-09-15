<?php

namespace App\Tests\Infrastructure\Company\Http;

use App\Infrastructure\Company\Http\CreateCompanyInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateCompanyInputTest extends TestCase
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
        $input = new CreateCompanyInput(name: 'Acme Corp', phone: '+261340000000');

        self::assertCount(0, $this->validator->validate($input));
    }

    public function testABlankNameIsRejected(): void
    {
        $input = new CreateCompanyInput(name: '');

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('company.name.not_blank', $messages['name'] ?? null);
    }

    public function testANameLongerThan255CharactersIsRejected(): void
    {
        $input = new CreateCompanyInput(name: str_repeat('a', 256));

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('company.name.too_long', $messages['name'] ?? null);
    }

    public function testAPhoneLongerThan15CharactersIsRejected(): void
    {
        $input = new CreateCompanyInput(name: 'Acme Corp', phone: str_repeat('1', 16));

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('company.phone.too_long', $messages['phone'] ?? null);
    }
}
