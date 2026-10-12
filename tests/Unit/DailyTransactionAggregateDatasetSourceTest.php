<?php

namespace Tests\Unit;

use App\Analytics\Datasets\AggregationFunction;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Queries\DatasetQuery;
use App\Analytics\Queries\Sources\DailyTransactionAggregateDatasetSource;
use App\Analytics\Time\ReportingTimezone;
use PHPUnit\Framework\TestCase;

final class DailyTransactionAggregateDatasetSourceTest extends TestCase
{
    public function test_source_targets_daily_aggregate(): void
    {
        $source = new DailyTransactionAggregateDatasetSource;

        $this->assertSame(
            DatasetKey::TRANSACTIONS,
            $source->dataset(),
        );

        $this->assertSame(
            'warehouse',
            $source->connectionName(),
        );

        $this->assertSame(
            'agg_daily_transaction_activity',
            $source->baseTable(),
        );

        $this->assertSame(
            'dim_branch.source_branch_id',
            $source->branchScopeColumn(),
        );
    }

    public function test_reporting_timezone_is_a_mandatory_constraint(): void
    {
        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['booking_date'],
            measures: ['transaction_count'],
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $constraints = (new DailyTransactionAggregateDatasetSource)
            ->constraints($query);

        $this->assertCount(1, $constraints);

        $this->assertSame(
            'agg_daily_transaction_activity.reporting_timezone = ?',
            $constraints[0]->sql,
        );

        $this->assertSame(
            ['Europe/Luxembourg'],
            $constraints[0]->bindings,
        );
    }

    public function test_transaction_count_sums_preaggregated_counts(): void
    {
        $measure = (new DailyTransactionAggregateDatasetSource)
            ->measureSource('transaction_count');

        $this->assertSame(
            'agg_daily_transaction_activity.transaction_count',
            $measure->column,
        );

        $this->assertSame(
            AggregationFunction::SUM,
            $measure->aggregationOverride,
        );
    }
}
