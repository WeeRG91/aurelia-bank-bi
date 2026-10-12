<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Filters\FilterCondition;
use App\Analytics\Filters\FilterOperator;
use App\Analytics\Queries\DatasetQuery;
use App\Analytics\Warehouse\Aggregates\DailyTransactionAggregateEligibility;
use PHPUnit\Framework\TestCase;

final class DailyTransactionAggregateEligibilityTest extends TestCase
{
    public function test_supported_daily_aggregate_query_is_eligible(): void
    {
        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: [
                'booking_date',
                'branch',
                'currency',
            ],
            measures: [
                'transaction_count',
                'net_cash_flow',
            ],
            filters: [
                $this->bookingDateFilter(),
            ],
        );

        $this->assertTrue(
            (new DailyTransactionAggregateEligibility)
                ->supports($query),
        );
    }

    public function test_query_without_measure_is_not_eligible(): void
    {
        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['booking_date'],
            filters: [
                $this->bookingDateFilter(),
            ],
        );

        $this->assertFalse(
            (new DailyTransactionAggregateEligibility)
                ->supports($query),
        );
    }

    public function test_unsupported_dimension_is_not_eligible(): void
    {
        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: [
                'booking_date',
                'account_type',
            ],
            measures: ['transaction_count'],
            filters: [
                $this->bookingDateFilter(),
            ],
        );

        $this->assertFalse(
            (new DailyTransactionAggregateEligibility)
                ->supports($query),
        );
    }

    public function test_unsupported_filter_is_not_eligible(): void
    {
        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['booking_date'],
            measures: ['transaction_count'],
            filters: [
                $this->bookingDateFilter(),
                new FilterCondition(
                    dataset: DatasetKey::TRANSACTIONS,
                    dimension: 'category',
                    operator: FilterOperator::EQUALS,
                    value: 'salary',
                ),
            ],
        );

        $this->assertFalse(
            (new DailyTransactionAggregateEligibility)
                ->supports($query),
        );
    }

    public function test_query_without_booking_date_constraint_is_not_eligible(): void
    {
        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: [
                'branch',
                'currency',
            ],
            measures: ['transaction_count'],
        );

        $this->assertFalse(
            (new DailyTransactionAggregateEligibility)
                ->supports($query),
        );
    }

    public function test_null_booking_date_query_is_not_eligible(): void
    {
        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['booking_date'],
            measures: ['transaction_count'],
            filters: [
                new FilterCondition(
                    dataset: DatasetKey::TRANSACTIONS,
                    dimension: 'booking_date',
                    operator: FilterOperator::IS_NULL,
                    value: null,
                ),
            ],
        );

        $this->assertFalse(
            (new DailyTransactionAggregateEligibility)
                ->supports($query),
        );
    }

    private function bookingDateFilter(): FilterCondition
    {
        return new FilterCondition(
            dataset: DatasetKey::TRANSACTIONS,
            dimension: 'booking_date',
            operator: FilterOperator::BETWEEN,
            value: [
                '2026-09-01',
                '2026-09-30',
            ],
        );
    }
}
