<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Filters\DimensionFilterRules;
use App\Analytics\Filters\FilterOperator;
use App\Analytics\Filters\FilterValidator;
use App\Analytics\Filters\InvalidFilter;
use PHPUnit\Framework\TestCase;

class FilterValidatorTest extends TestCase
{
    public function test_scalar_filter_is_normalized(): void
    {
        $condition = $this->validator()->validate(
            'transactions',
            'currency',
            'equals',
            ' EUR ',
        );

        $this->assertSame(
            DatasetKey::TRANSACTIONS,
            $condition->dataset,
        );

        $this->assertSame('currency', $condition->dimension);
        $this->assertSame(FilterOperator::EQUALS, $condition->operator);
        $this->assertSame('EUR', $condition->value);
    }

    public function test_list_filter_is_normalized(): void
    {
        $condition = $this->validator()->validate(
            DatasetKey::TRANSACTIONS,
            'status',
            FilterOperator::IN,
            ['pending', 'booked'],
        );

        $this->assertSame(
            ['pending', 'booked'],
            $condition->value,
        );
    }

    public function test_datetime_is_normalized_to_utc(): void
    {
        $condition = $this->validator()->validate(
            'transactions',
            'booked_at',
            'after',
            '2026-09-01T12:30:00+02:00',
        );

        $this->assertSame(
            '2026-09-01T10:30:00+00:00',
            $condition->value,
        );
    }

    public function test_nullable_dimension_accepts_null_operator(): void
    {
        $condition = $this->validator()->validate(
            'transactions',
            'booked_at',
            'is_null',
        );

        $this->assertNull($condition->value);
    }

    public function test_unknown_dimension_is_rejected(): void
    {
        $this->expectException(InvalidFilter::class);
        $this->expectExceptionMessage(
            'Unknown dimension [password] for dataset [transactions].',
        );

        $this->validator()->validate(
            'transactions',
            'password',
            'equals',
            'secret',
        );
    }

    public function test_incompatible_operator_is_rejected(): void
    {
        $this->expectException(InvalidFilter::class);
        $this->expectExceptionMessage(
            'Operator [before] is not supported for dimension [currency].',
        );

        $this->validator()->validate(
            'transactions',
            'currency',
            'before',
            'EUR',
        );
    }

    public function test_invalid_list_shape_is_rejected(): void
    {
        $this->expectException(InvalidFilter::class);
        $this->expectExceptionMessage(
            'Operator [in] requires a non-empty list with at most 100 values.',
        );

        $this->validator()->validate(
            'transactions',
            'status',
            'in',
            [],
        );
    }

    public function test_invalid_date_is_rejected(): void
    {
        $this->expectException(InvalidFilter::class);
        $this->expectExceptionMessage(
            'Invalid date value for dimension [value_date].',
        );

        $this->validator()->validate(
            'transactions',
            'value_date',
            'equals',
            '2026-02-30',
        );
    }

    public function test_reversed_between_boundaries_are_rejected(): void
    {
        $this->expectException(InvalidFilter::class);
        $this->expectExceptionMessage(
            'The lower filter boundary must not be greater than the upper boundary.',
        );

        $this->validator()->validate(
            'account_balances',
            'snapshot_date',
            'between',
            ['2026-09-30', '2026-09-01'],
        );
    }

    private function validator(): FilterValidator
    {
        return new FilterValidator(
            new DatasetRegistry,
            new DimensionFilterRules,
        );
    }
}
