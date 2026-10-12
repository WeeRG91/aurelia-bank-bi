<?php

namespace App\Analytics\Queries\Sources;

use App\Analytics\Datasets\AggregationFunction;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\UnknownDimension;
use App\Analytics\Datasets\UnknownMeasure;
use App\Analytics\Queries\Compilation\CompiledFilter;
use App\Analytics\Queries\DatasetQuery;

final class DailyTransactionAggregateDatasetSource implements ConstrainedDatasetSource
{
    public function dataset(): DatasetKey
    {
        return DatasetKey::TRANSACTIONS;
    }

    public function connectionName(): string
    {
        return 'warehouse';
    }

    public function baseTable(): string
    {
        return 'agg_daily_transaction_activity';
    }

    public function branchScopeColumn(): string
    {
        return 'dim_branch.source_branch_id';
    }

    public function constraints(
        DatasetQuery $query,
    ): array {
        return [
            new CompiledFilter(
                sql: 'agg_daily_transaction_activity.reporting_timezone = ?',
                bindings: [
                    $query->reportingTimezone->name,
                ],
            ),
        ];
    }

    public function joins(): array
    {
        return [
            new JoinDefinition(
                table: 'dim_branch',
                leftColumn: 'agg_daily_transaction_activity.branch_key',
                rightColumn: 'dim_branch.branch_key',
            ),
            new JoinDefinition(
                table: 'dim_date',
                leftColumn: 'agg_daily_transaction_activity.reporting_date_key',
                rightColumn: 'dim_date.date_key',
            ),
        ];
    }

    public function dimensionSources(): array
    {
        return [
            'branch' => new DimensionSource(
                column: 'dim_branch.branch_code',
            ),
            'country' => new DimensionSource(
                column: 'dim_branch.country_code',
            ),
            'transaction_type' => new DimensionSource(
                column: 'agg_daily_transaction_activity.transaction_type',
            ),
            'currency' => new DimensionSource(
                column: 'agg_daily_transaction_activity.currency',
            ),
            'booking_date' => new DimensionSource(
                column: 'dim_date.calendar_date',
            ),
        ];
    }

    public function dimensionSource(
        string $dimension,
    ): DimensionSource {
        return $this->dimensionSources()[$dimension]
            ?? throw UnknownDimension::forDataset(
                $this->dataset(),
                $dimension,
            );
    }

    public function measureSources(): array
    {
        return [
            'transaction_count' => new MeasureSource(
                column: 'agg_daily_transaction_activity.transaction_count',
                aggregationOverride: AggregationFunction::SUM,
            ),
            'total_amount' => new MeasureSource(
                column: 'agg_daily_transaction_activity.total_amount',
            ),
            'incoming_amount' => new MeasureSource(
                column: 'agg_daily_transaction_activity.incoming_amount',
            ),
            'outgoing_amount' => new MeasureSource(
                column: 'agg_daily_transaction_activity.outgoing_amount',
            ),
            'net_cash_flow' => new MeasureSource(
                column: 'agg_daily_transaction_activity.net_cash_flow',
            ),
        ];
    }

    public function measureSource(
        string $measure,
    ): MeasureSource {
        return $this->measureSources()[$measure]
            ?? throw UnknownMeasure::forDataset(
                $this->dataset(),
                $measure,
            );
    }
}
