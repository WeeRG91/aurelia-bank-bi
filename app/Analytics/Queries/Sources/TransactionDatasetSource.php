<?php

namespace App\Analytics\Queries\Sources;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\UnknownDimension;
use App\Analytics\Datasets\UnknownMeasure;

final class TransactionDatasetSource implements DatasetSource
{
    public function dataset(): DatasetKey
    {
        return DatasetKey::TRANSACTIONS;
    }

    public function baseTable(): string
    {
        return 'transactions';
    }

    public function joins(): array
    {
        return [
            new JoinDefinition(
                table: 'accounts',
                leftColumn: 'transactions.account_id',
                rightColumn: 'accounts.id',
            ),
            new JoinDefinition(
                table: 'branches',
                leftColumn: 'accounts.branch_id',
                rightColumn: 'branches.id',
            ),
        ];
    }

    public function branchScopeColumn(): string
    {
        return 'branches.id';
    }

    public function measureSources(): array
    {
        return [
            'transaction_count' => new MeasureSource(
                column: 'transactions.id',
            ),
            'total_amount' => new MeasureSource(
                column: 'transactions.amount',
            ),
            'incoming_amount' => new MeasureSource(
                column: 'transactions.amount',
                kind: MeasureSourceKind::INCOMING_AMOUNT,
                directionColumn: 'transactions.direction',
            ),
            'outgoing_amount' => new MeasureSource(
                column: 'transactions.amount',
                kind: MeasureSourceKind::OUTGOING_AMOUNT,
                directionColumn: 'transactions.direction',
            ),
            'net_cash_flow' => new MeasureSource(
                column: 'transactions.amount',
                kind: MeasureSourceKind::NET_AMOUNT,
                directionColumn: 'transactions.direction',
            ),
        ];
    }

    public function measureSource(string $measure): MeasureSource
    {
        return $this->measureSources()[$measure]
            ?? throw UnknownMeasure::forDataset(
                $this->dataset(),
                $measure,
            );
    }

    public function dimensionSources(): array
    {
        return [
            'transaction_reference' => new DimensionSource(
                column: 'transactions.transaction_reference',
            ),
            'branch' => new DimensionSource(
                column: 'branches.branch_code',
            ),
            'country' => new DimensionSource(
                column: 'branches.country_code',
            ),
            'account_type' => new DimensionSource(
                column: 'accounts.account_type',
            ),
            'transaction_type' => new DimensionSource(
                column: 'transactions.transaction_type',
            ),
            'category' => new DimensionSource(
                column: 'transactions.category',
            ),
            'currency' => new DimensionSource(
                column: 'transactions.currency',
            ),
            'direction' => new DimensionSource(
                column: 'transactions.direction',
            ),
            'status' => new DimensionSource(
                column: 'transactions.status',
            ),
            'booked_at' => new DimensionSource(
                column: 'transactions.booked_at',
            ),
            'value_date' => new DimensionSource(
                column: 'transactions.value_date',
            ),
        ];
    }

    public function dimensionSource(string $dimension): DimensionSource
    {
        return $this->dimensionSources()[$dimension]
            ?? throw UnknownDimension::forDataset(
                $this->dataset(),
                $dimension,
            );
    }
}
