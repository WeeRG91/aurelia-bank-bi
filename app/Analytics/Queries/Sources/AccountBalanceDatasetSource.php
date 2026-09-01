<?php

namespace App\Analytics\Queries\Sources;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\UnknownDimension;
use App\Analytics\Datasets\UnknownMeasure;

final class AccountBalanceDatasetSource implements DatasetSource
{
    /**
     * @var array<string, string>
     */
    private const array COLUMNS = [
        'account_number' => 'accounts.account_number',
        'branch' => 'branches.branch_code',
        'country' => 'branches.country_code',
        'account_type' => 'accounts.account_type',
        'currency' => 'accounts.currency',
        'account_status' => 'accounts.status',
        'snapshot_date' => 'account_balance_snapshots.snapshot_date',
    ];

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

    public function columns(): array
    {
        return self::COLUMNS;
    }

    public function column(string $dimension): string
    {
        return self::COLUMNS[$dimension]
            ?? throw UnknownDimension::forDataset(
                $this->dataset(),
                $dimension,
            );
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
}
