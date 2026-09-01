<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Filters\DimensionFilterRules;
use App\Analytics\Filters\FilterOperator;
use PHPUnit\Framework\TestCase;

class DimensionFilterRulesTest extends TestCase
{
    public function test_filter_operator_values_are_stable(): void
    {
        $this->assertSame(
            [
                'equals',
                'not_equals',
                'in',
                'not_in',
                'before',
                'after',
                'on_or_after',
                'between',
                'is_null',
                'is_not_null',
            ],
            array_map(
                fn (FilterOperator $operator): string => $operator->value,
                FilterOperator::cases(),
            ),
        );
    }

    public function test_categorical_dimension_supports_equality_and_list_operators(): void
    {
        $dimension = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS)
            ->dimension('currency');

        $operators = (new DimensionFilterRules)
            ->allowedOperators($dimension);

        $this->assertSame(
            [
                FilterOperator::EQUALS,
                FilterOperator::NOT_EQUALS,
                FilterOperator::IN,
                FilterOperator::NOT_IN,
            ],
            $operators,
        );
    }

    public function test_nullable_temporal_dimension_supports_time_and_null_operators(): void
    {
        $dimension = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS)
            ->dimension('booked_at');

        $operators = (new DimensionFilterRules)
            ->allowedOperators($dimension);

        $this->assertSame(
            [
                FilterOperator::EQUALS,
                FilterOperator::NOT_EQUALS,
                FilterOperator::BEFORE,
                FilterOperator::AFTER,
                FilterOperator::ON_OR_AFTER,
                FilterOperator::BETWEEN,
                FilterOperator::IS_NULL,
                FilterOperator::IS_NOT_NULL,
            ],
            $operators,
        );
    }

    public function test_required_temporal_dimension_does_not_support_null_operators(): void
    {
        $dimension = (new DatasetRegistry)
            ->get(DatasetKey::ACCOUNT_BALANCES)
            ->dimension('snapshot_date');

        $rules = new DimensionFilterRules;

        $this->assertFalse(
            $rules->supports(
                $dimension,
                FilterOperator::IS_NULL,
            ),
        );

        $this->assertFalse(
            $rules->supports(
                $dimension,
                FilterOperator::IS_NOT_NULL,
            ),
        );

        $this->assertTrue(
            $rules->supports(
                $dimension,
                FilterOperator::BEFORE,
            ),
        );
    }

    public function test_categorical_dimension_rejects_temporal_operator(): void
    {
        $dimension = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS)
            ->dimension('status');

        $this->assertFalse(
            (new DimensionFilterRules)->supports(
                $dimension,
                FilterOperator::BEFORE,
            ),
        );
    }
}
