<?php

namespace App\Analytics\Queries\Compilation;

use App\Analytics\Filters\FilterCondition;
use App\Analytics\Filters\FilterOperator;
use App\Analytics\Queries\Sources\DatasetSource;
use App\Analytics\Time\ReportingTimezone;
use LogicException;

final class FilterCompiler
{
    public function __construct(
        private DimensionSourceCompiler $dimensionSourceCompiler = new DimensionSourceCompiler,
    ) {}

    public function compile(
        DatasetSource $source,
        FilterCondition $condition,
        ReportingTimezone $reportingTimezone = new ReportingTimezone('UTC'),
    ): CompiledFilter {
        if ($source->dataset() !== $condition->dataset) {
            throw new LogicException(
                "Filter dataset [{$condition->dataset->value}] does not match source dataset [{$source->dataset()->value}].",
            );
        }

        $column = $this->dimensionSourceCompiler->compile(
            $source->dimensionSource($condition->dimension),
            $reportingTimezone,
        );

        return match ($condition->operator) {
            FilterOperator::EQUALS => $this->compileScalar(
                $condition,
                $column,
                '=',
            ),
            FilterOperator::NOT_EQUALS => $this->compileScalar(
                $condition,
                $column,
                '<>',
            ),
            FilterOperator::BEFORE => $this->compileScalar(
                $condition,
                $column,
                '<',
            ),
            FilterOperator::AFTER => $this->compileScalar(
                $condition,
                $column,
                '>',
            ),
            FilterOperator::IN => $this->compileList(
                $condition,
                $column,
                negated: false,
            ),
            FilterOperator::NOT_IN => $this->compileList(
                $condition,
                $column,
                negated: true,
            ),
            FilterOperator::BETWEEN => $this->compileBetween(
                $condition,
                $column,
            ),
            FilterOperator::IS_NULL => $this->compileNull(
                $condition,
                $column,
                negated: false,
            ),
            FilterOperator::IS_NOT_NULL => $this->compileNull(
                $condition,
                $column,
                negated: true,
            ),
            FilterOperator::ON_OR_AFTER => $this->compileScalar(
                $condition,
                $column,
                '>=',
            ),
        };
    }

    private function compileScalar(
        FilterCondition $condition,
        string $column,
        string $operator,
    ): CompiledFilter {
        if (
            $condition->value === null
            || is_array($condition->value)
        ) {
            throw $this->invalidValue($condition);
        }

        return new CompiledFilter(
            sql: "{$column} {$operator} ?",
            bindings: [$condition->value],
        );
    }

    private function compileList(
        FilterCondition $condition,
        string $column,
        bool $negated,
    ): CompiledFilter {
        if (
            ! is_array($condition->value)
            || ! array_is_list($condition->value)
            || $condition->value === []
        ) {
            throw $this->invalidValue($condition);
        }

        $placeholders = implode(
            ', ',
            array_fill(0, count($condition->value), '?'),
        );

        $operator = $negated ? 'NOT IN' : 'IN';

        return new CompiledFilter(
            sql: "{$column} {$operator} ({$placeholders})",
            bindings: $condition->value,
        );
    }

    private function compileBetween(
        FilterCondition $condition,
        string $column,
    ): CompiledFilter {
        if (
            ! is_array($condition->value)
            || ! array_is_list($condition->value)
            || count($condition->value) !== 2
        ) {
            throw $this->invalidValue($condition);
        }

        return new CompiledFilter(
            sql: "{$column} BETWEEN ? AND ?",
            bindings: $condition->value,
        );
    }

    private function compileNull(
        FilterCondition $condition,
        string $column,
        bool $negated,
    ): CompiledFilter {
        if ($condition->value !== null) {
            throw $this->invalidValue($condition);
        }

        $operator = $negated ? 'IS NOT NULL' : 'IS NULL';

        return new CompiledFilter(
            sql: "{$column} {$operator}",
            bindings: [],
        );
    }

    private function invalidValue(
        FilterCondition $condition
    ): LogicException {
        return new LogicException(
            "Invalid trusted value for operator [{$condition->operator->value}].",
        );
    }
}
