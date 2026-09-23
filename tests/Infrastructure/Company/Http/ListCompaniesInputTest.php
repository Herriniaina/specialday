<?php

namespace App\Tests\Infrastructure\Company\Http;

use App\Infrastructure\Company\Http\ListCompaniesInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ListCompaniesInputTest extends TestCase
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

    public function testTheDefaultInputHasNoViolations(): void
    {
        self::assertCount(0, $this->validator->validate(new ListCompaniesInput()));
    }

    public function testAValidInputWithFiltersHasNoViolations(): void
    {
        $input = new ListCompaniesInput(name: 'eq:Nom company', prestation: 'in:photo,video', page: 2, limit: 50);

        self::assertCount(0, $this->validator->validate($input));
    }

    public function testAZeroPageIsRejected(): void
    {
        $input = new ListCompaniesInput(page: 0);

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('company.list.page.positive', $messages['page'] ?? null);
    }

    public function testANegativePageIsRejected(): void
    {
        $input = new ListCompaniesInput(page: -1);

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('company.list.page.positive', $messages['page'] ?? null);
    }

    public function testAZeroLimitIsRejected(): void
    {
        $input = new ListCompaniesInput(limit: 0);

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('company.list.limit.range', $messages['limit'] ?? null);
    }

    public function testALimitAbove100IsRejected(): void
    {
        $input = new ListCompaniesInput(limit: 101);

        $messages = $this->messagesByPropertyPath($this->validator->validate($input));

        self::assertSame('company.list.limit.range', $messages['limit'] ?? null);
    }
}
