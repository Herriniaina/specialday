<?php

namespace App\Infrastructure\Shared\Http\Filter;

use App\Domain\Shared\Filter;
use App\Domain\Shared\FilterOperator;

/**
 * Parses query filter values shaped as "eq:value", "noteq:value",
 * "in:value1,value2", "notin:value1,value2" or "contains:value". A value
 * with no recognized operator prefix (e.g. "value") is treated as "eq:value".
 */
final class FilterQueryParser
{
    /**
     * @param FilterOperator[] $allowedOperators operators accepted for this particular field; empty means all of them
     */
    public static function parse(string $raw, array $allowedOperators = []): Filter
    {
        if ([] === $allowedOperators) {
            $allowedOperators = FilterOperator::cases();
        }

        $operator = FilterOperator::EQ;
        $rawValue = $raw;

        if (1 === preg_match('/^([a-z]+):(.*)$/s', $raw, $matches)) {
            $operator = FilterOperator::tryFrom($matches[1])
                ?? throw new InvalidFilterOperatorException(\sprintf('Unknown filter operator "%s".', $matches[1]));
            $rawValue = $matches[2];
        }

        if (!\in_array($operator, $allowedOperators, true)) {
            throw new InvalidFilterOperatorException(\sprintf('Filter operator "%s" is not supported for this field.', $operator->value));
        }

        $values = match ($operator) {
            FilterOperator::IN, FilterOperator::NOT_IN => array_values(array_filter(
                array_map('trim', explode(',', $rawValue)),
                static fn (string $value): bool => '' !== $value,
            )),
            default => [$rawValue],
        };

        if ([] === $values) {
            throw new InvalidFilterOperatorException('Filter requires at least one value.');
        }

        return new Filter($operator, $values);
    }
}
