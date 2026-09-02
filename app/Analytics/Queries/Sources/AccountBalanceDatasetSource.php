<?php

namespace App\Analytics\Queries\Sources;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\UnknownDimension;
use App\Analytics\Datasets\UnknownMeasure;

final class AccountBalanceDatasetSource implements DatasetSource
{
    public function dataset(): DatasetKey
    {
        return DatasetKey::ACCOUNT_BALANCES;
    }

    public function baseTable(): string
    {
        return 'account_balance_snapshots';
    }

    public function branchScopeColumn(): string
    {
        return 'branches.id';
    }

    public function measureSources(): array
    {
        return [
            'snapshot_count' => new MeasureSource(
                column: 'account_balance_snapshots.id',
            ),
            'total_ledger_balance' => new MeasureSource(
                column: 'account_balance_snapshots.ledger_balance',
            ),
            'total_available_balance' => new MeasureSource(
                column: 'account_balance_snapshots.available_balance',
            ),
            'average_ledger_balance' => new MeasureSource(
                column: 'account_balance_snapshots.ledger_balance',
            ),
            'average_available_balance' => new MeasureSource(
                column: 'account_balance_snapshots.available_balance',
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

    public function joins(): array
    {
        return [
            new JoinDefinition(
                table: 'accounts',
                leftColumn: 'account_balance_snapshots.account_id',
                rightColumn: 'accounts.id',
            ),
            new JoinDefinition(
                table: 'branches',
                leftColumn: 'accounts.branch_id',
                rightColumn: 'branches.id',
            ),
        ];
    }

    public function dimensionSources(): array
    {
        return [
            'account_number' => new DimensionSource(
                column: 'accounts.account_number',
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
            'currency' => new DimensionSource(
                column: 'accounts.currency',
            ),
            'account_status' => new DimensionSource(
                column: 'accounts.status',
            ),
            'snapshot_date' => new DimensionSource(
                column: 'account_balance_snapshots.snapshot_date',
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
