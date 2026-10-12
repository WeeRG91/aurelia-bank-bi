<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Filters\FilterCondition;
use App\Analytics\Filters\FilterOperator;
use App\Analytics\Queries\DatasetQuery;
use App\Analytics\Queries\Sources\DailyTransactionAggregateDatasetSource;
use App\Analytics\Queries\Sources\TransactionDatasetSource;
use App\Analytics\Queries\Sources\TransactionDatasetSourceRouter;
use App\Analytics\Queries\Sources\WarehouseTransactionDatasetSource;
use App\Analytics\Time\ReportingTimezone;
use Tests\TestCase;

final class TransactionDatasetSourceRouterTest extends TestCase
{
    public function test_eligible_query_uses_daily_aggregate(): void
    {
        config([
            'analytics.reporting_timezone' => 'Europe/Luxembourg',
        ]);

        $source = (new TransactionDatasetSourceRouter)->route(
            new WarehouseTransactionDatasetSource,
            $this->eligibleQuery(),
        );

        $this->assertInstanceOf(
            DailyTransactionAggregateDatasetSource::class,
            $source,
        );
    }

    public function test_unsupported_query_uses_fact_source(): void
    {
        config([
            'analytics.reporting_timezone' => 'Europe/Luxembourg',
        ]);

        $fallback = new WarehouseTransactionDatasetSource;

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
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $this->assertSame(
            $fallback,
            (new TransactionDatasetSourceRouter)->route(
                $fallback,
                $query,
            ),
        );
    }

    public function test_unrefreshed_timezone_uses_fact_source(): void
    {
        config([
            'analytics.reporting_timezone' => 'Europe/Luxembourg',
        ]);

        $fallback = new WarehouseTransactionDatasetSource;

        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: [
                'booking_date',
                'currency',
            ],
            measures: ['transaction_count'],
            filters: [
                $this->bookingDateFilter(),
            ],
            reportingTimezone: new ReportingTimezone('UTC'),
        );

        $this->assertSame(
            $fallback,
            (new TransactionDatasetSourceRouter)->route(
                $fallback,
                $query,
            ),
        );
    }

    public function test_explicit_operational_source_is_not_replaced(): void
    {
        config([
            'analytics.reporting_timezone' => 'Europe/Luxembourg',
        ]);

        $operationalSource = new TransactionDatasetSource;

        $this->assertSame(
            $operationalSource,
            (new TransactionDatasetSourceRouter)->route(
                $operationalSource,
                $this->eligibleQuery(),
            ),
        );
    }

    private function eligibleQuery(): DatasetQuery
    {
        return new DatasetQuery(
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
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
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
