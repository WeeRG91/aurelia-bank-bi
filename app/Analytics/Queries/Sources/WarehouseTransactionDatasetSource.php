<?php

namespace App\Analytics\Queries\Sources;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\UnknownDimension;
use App\Analytics\Datasets\UnknownMeasure;

final class WarehouseTransactionDatasetSource implements DatasetSource
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
        return 'fact_transactions';
    }

    public function branchScopeColumn(): string
    {
        return 'dim_branch.source_branch_id';
    }

    public function joins(): array
    {
        return [
            new JoinDefinition(
                table: 'dim_account',
                leftColumn: 'fact_transactions.account_key',
                rightColumn: 'dim_account.account_key',
            ),
            new JoinDefinition(
                table: 'dim_branch',
                leftColumn: 'fact_transactions.branch_key',
                rightColumn: 'dim_branch.branch_key',
            ),
            new JoinDefinition(
                table: 'dim_date',
                leftColumn: 'fact_transactions.value_date_key',
                rightColumn: 'dim_date.date_key',
                type: JoinType::LEFT,
            ),
        ];
    }

    public function dimensionSources(): array
    {
        return [
            'transaction_reference' => new DimensionSource(
                column: 'fact_transactions.transaction_reference',
            ),
            'branch' => new DimensionSource(
                column: 'dim_branch.branch_code',
            ),
            'country' => new DimensionSource(
                column: 'dim_branch.country_code',
            ),
            'account_type' => new DimensionSource(
                column: 'dim_account.account_type',
            ),
            'transaction_type' => new DimensionSource(
                column: 'fact_transactions.transaction_type',
            ),
            'category' => new DimensionSource(
                column: 'fact_transactions.category',
            ),
            'currency' => new DimensionSource(
                column: 'fact_transactions.currency',
            ),
            'direction' => new DimensionSource(
                column: 'fact_transactions.direction',
            ),
            'status' => new DimensionSource(
                column: 'fact_transactions.transaction_status',
            ),
            'booked_at' => new DimensionSource(
                column: 'fact_transactions.booked_at',
            ),
            'booking_date' => new DimensionSource(
                column: 'fact_transactions.booked_at',
                kind: DimensionSourceKind::LOCAL_DATE,
            ),
            'booking_month' => new DimensionSource(
                column: 'fact_transactions.booked_at',
                kind: DimensionSourceKind::LOCAL_MONTH,
            ),
            'booking_quarter' => new DimensionSource(
                column: 'fact_transactions.booked_at',
                kind: DimensionSourceKind::LOCAL_QUARTER,
            ),
            'booking_year' => new DimensionSource(
                column: 'fact_transactions.booked_at',
                kind: DimensionSourceKind::LOCAL_YEAR,
            ),
            'value_date' => new DimensionSource(
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
                column: 'fact_transactions.source_transaction_id',
            ),
            'total_amount' => new MeasureSource(
                column: 'fact_transactions.absolute_amount',
            ),
            'incoming_amount' => new MeasureSource(
                column: 'fact_transactions.absolute_amount',
                kind: MeasureSourceKind::INCOMING_AMOUNT,
                directionColumn: 'fact_transactions.direction',
            ),
            'outgoing_amount' => new MeasureSource(
                column: 'fact_transactions.absolute_amount',
                kind: MeasureSourceKind::OUTGOING_AMOUNT,
                directionColumn: 'fact_transactions.direction',
            ),
            'net_cash_flow' => new MeasureSource(
                column: 'fact_transactions.signed_amount',
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
