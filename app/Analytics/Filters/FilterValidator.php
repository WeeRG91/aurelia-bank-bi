<?php

namespace App\Analytics\Filters;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\DimensionDefinition;
use App\Analytics\Datasets\FieldDataType;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final readonly class FilterValidator
{
    private const int MAX_LIST_VALUES = 100;

    public function __construct(
        private DatasetRegistry $registry,
        private DimensionFilterRules $rules,
    ) {}

    public function validate(
        DatasetKey|string $datasetIdentifier,
        string $dimensionKey,
        FilterOperator|string $operatorIdentifier,
        mixed $value = null,
    ): FilterCondition {
        $dataset = $this->registry->find($datasetIdentifier);

        if ($dataset === null) {
            $identifier = $datasetIdentifier instanceof DatasetKey
                ? $datasetIdentifier->value
                : $datasetIdentifier;

            throw new InvalidFilter(
                "Unknown dataset [{$identifier}].",
            );
        }

        $dimension = $dataset->findDimension($dimensionKey);

        if ($dimension === null) {
            throw new InvalidFilter(
                "Unknown dimension [{$dimensionKey}] for dataset [{$dataset->key->value}].",
            );
        }

        $operator = $operatorIdentifier instanceof FilterOperator
            ? $operatorIdentifier
            : FilterOperator::tryFrom($operatorIdentifier);

        if ($operator === null) {
            throw new InvalidFilter(
                "Unknown filter operator [{$operatorIdentifier}].",
            );
        }

        if (! $this->rules->supports($dimension, $operator)) {
            throw new InvalidFilter(
                "Operator [{$operator->value}] is not supported for dimension [{$dimension->key}].",
            );
        }

        return new FilterCondition(
            dataset: $dataset->key,
            dimension: $dimension->key,
            operator: $operator,
            value: $this->normalizeValue(
                $dimension,
                $operator,
                $value,
            ),
        );
    }

    private function normalizeValue(
        DimensionDefinition $dimension,
        FilterOperator $operator,
        mixed $value,
    ): string|int|bool|array|null {
        return match ($operator) {
            FilterOperator::IS_NULL,
            FilterOperator::IS_NOT_NULL => $this->normalizeNullOperator(
                $operator,
                $value,
            ),

            FilterOperator::IN,
            FilterOperator::NOT_IN => $this->normalizeList(
                $dimension,
                $operator,
                $value,
            ),

            FilterOperator::BETWEEN => $this->normalizeBetween(
                $dimension,
                $value,
            ),

            default => $this->normalizeScalar(
                $dimension,
                $value,
            ),
        };
    }

    private function normalizeNullOperator(
        FilterOperator $operator,
        mixed $value,
    ): null {
        if ($value !== null) {
            throw new InvalidFilter(
                "Operator [{$operator->value}] does not accept a value.",
            );
        }

        return null;
    }

    /**
     * @return list<string|int|bool>
     */
    private function normalizeList(
        DimensionDefinition $dimension,
        FilterOperator $operator,
        mixed $value,
    ): array {
        if (
            ! is_array($value)
            || ! array_is_list($value)
            || $value === []
            || count($value) > self::MAX_LIST_VALUES
        ) {
            throw new InvalidFilter(
                "Operator [{$operator->value}] requires a non-empty list with at most 100 values.",
            );
        }

        return array_map(
            fn (mixed $item): string|int|bool => $this->normalizeScalar(
                $dimension,
                $item,
            ),
            $value,
        );
    }

    /**
     * @return list<string|int|bool>
     */
    private function normalizeBetween(
        DimensionDefinition $dimension,
        mixed $value,
    ): array {
        if (
            ! is_array($value)
            || ! array_is_list($value)
            || count($value) !== 2
        ) {
            throw new InvalidFilter(
                'Operator [between] requires exactly two values.',
            );
        }

        $normalized = [
            $this->normalizeScalar($dimension, $value[0]),
            $this->normalizeScalar($dimension, $value[1]),
        ];

        if ($normalized[0] > $normalized[1]) {
            throw new InvalidFilter(
                'The lower filter boundary must not be greater than the upper boundary.',
            );
        }

        return $normalized;
    }

    private function normalizeScalar(
        DimensionDefinition $dimension,
        mixed $value,
    ): string|int|bool {
        return match ($dimension->dataType) {
            FieldDataType::STRING => $this->normalizeString(
                $dimension,
                $value,
            ),
            FieldDataType::DATE => $this->normalizeDate(
                $dimension,
                $value,
            ),
            FieldDataType::DATETIME => $this->normalizeDateTime(
                $dimension,
                $value,
            ),
            FieldDataType::BOOLEAN => is_bool($value)
                ? $value
                : throw new InvalidFilter(
                    "Invalid boolean value for dimension [{$dimension->key}].",
                ),
            FieldDataType::INTEGER => is_int($value)
                ? $value
                : throw new InvalidFilter(
                    "Invalid integer value for dimension [{$dimension->key}].",
                ),
            FieldDataType::DECIMAL => $this->normalizeDecimal(
                $dimension,
                $value,
            ),
        };
    }

    private function normalizeString(
        DimensionDefinition $dimension,
        mixed $value,
    ): string {
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidFilter(
                "Invalid string value for dimension [{$dimension->key}].",
            );
        }

        return trim($value);
    }

    private function normalizeDate(
        DimensionDefinition $dimension,
        mixed $value,
    ): string {
        if (! is_string($value)) {
            throw new InvalidFilter(
                "Invalid date value for dimension [{$dimension->key}].",
            );
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value,
        );

        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new InvalidFilter(
                "Invalid date value for dimension [{$dimension->key}].",
            );
        }

        return $value;
    }

    private function normalizeDateTime(
        DimensionDefinition $dimension,
        mixed $value,
    ): string {
        if (! is_string($value)) {
            throw new InvalidFilter(
                "Invalid datetime value for dimension [{$dimension->key}].",
            );
        }

        $dateTime = DateTimeImmutable::createFromFormat(
            DateTimeInterface::ATOM,
            $value,
        );

        if (
            $dateTime === false
            || $dateTime->format(DateTimeInterface::ATOM) !== $value
        ) {
            throw new InvalidFilter(
                "Invalid datetime value for dimension [{$dimension->key}].",
            );
        }

        return $dateTime
            ->setTimezone(new DateTimeZone('UTC'))
            ->format(DateTimeInterface::ATOM);
    }

    private function normalizeDecimal(
        DimensionDefinition $dimension,
        mixed $value,
    ): string {
        if (is_int($value)) {
            return (string) $value;
        }

        if (
            ! is_string($value)
            || preg_match('/^-?\d+(?:\.\d+)?$/', trim($value)) !== 1
        ) {
            throw new InvalidFilter(
                "Invalid decimal value for dimension [{$dimension->key}].",
            );
        }

        return trim($value);
    }
}
