<?php

namespace App\Tests\Infrastructure\Shared\Http\Filter;

use App\Domain\Shared\FilterOperator;
use App\Infrastructure\Shared\Http\Filter\FilterQueryParser;
use App\Infrastructure\Shared\Http\Filter\InvalidFilterOperatorException;
use PHPUnit\Framework\TestCase;

class FilterQueryParserTest extends TestCase
{
    public function testAValueWithoutPrefixDefaultsToEq(): void
    {
        $filter = FilterQueryParser::parse('Nom company');

        self::assertSame(FilterOperator::EQ, $filter->operator);
        self::assertSame(['Nom company'], $filter->values);
    }

    public function testAnEqPrefixIsParsed(): void
    {
        $filter = FilterQueryParser::parse('eq:Nom company');

        self::assertSame(FilterOperator::EQ, $filter->operator);
        self::assertSame(['Nom company'], $filter->values);
    }

    public function testANotEqPrefixIsParsed(): void
    {
        $filter = FilterQueryParser::parse('noteq:Nom company');

        self::assertSame(FilterOperator::NOT_EQ, $filter->operator);
        self::assertSame(['Nom company'], $filter->values);
    }

    public function testAnInPrefixSplitsValuesOnComma(): void
    {
        $filter = FilterQueryParser::parse('in:photo,video');

        self::assertSame(FilterOperator::IN, $filter->operator);
        self::assertSame(['photo', 'video'], $filter->values);
    }

    public function testANotinPrefixSplitsValuesOnComma(): void
    {
        $filter = FilterQueryParser::parse('notin:photo,video');

        self::assertSame(FilterOperator::NOT_IN, $filter->operator);
        self::assertSame(['photo', 'video'], $filter->values);
    }

    public function testInValuesAreTrimmedAndEmptyEntriesAreDropped(): void
    {
        $filter = FilterQueryParser::parse('in: photo , , video ');

        self::assertSame(['photo', 'video'], $filter->values);
    }

    public function testAContainsPrefixIsParsedAsASingleValue(): void
    {
        $filter = FilterQueryParser::parse('contains:lenses');

        self::assertSame(FilterOperator::CONTAINS, $filter->operator);
        self::assertSame(['lenses'], $filter->values);
    }

    public function testAnUnknownOperatorPrefixIsRejected(): void
    {
        $this->expectException(InvalidFilterOperatorException::class);

        FilterQueryParser::parse('like:photo');
    }

    public function testAnInFilterWithNoValuesIsRejected(): void
    {
        $this->expectException(InvalidFilterOperatorException::class);

        FilterQueryParser::parse('in: , ,');
    }

    public function testAnOperatorNotInTheAllowedListIsRejected(): void
    {
        $this->expectException(InvalidFilterOperatorException::class);

        FilterQueryParser::parse('contains:lenses', [FilterOperator::EQ, FilterOperator::NOT_EQ, FilterOperator::IN, FilterOperator::NOT_IN]);
    }

    public function testAnOperatorInTheAllowedListIsAccepted(): void
    {
        $filter = FilterQueryParser::parse('contains:lenses', [FilterOperator::CONTAINS]);

        self::assertSame(FilterOperator::CONTAINS, $filter->operator);
    }
}
