<?php

namespace App\Analytics\Queries\Sources;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\UnknownDimension;
use App\Analytics\Datasets\UnknownMeasure;

final class TransactionDatasetSource implements DatasetSource
{
    /**
     * @var array<string, string>
     */
    private const array COLUMNS = [
        'transaction_reference' => 'transactions.transaction_reference',
        'branch' => 'branches.branch_code',
        'country' => 'branches.country_code',
        'account_type' => 'accounts.account_type',
        'transaction_type' => 'transactions.transaction_type',
        'category' => 'transactions.category',
        'currency' => 'transactions.currency',
        'direction' => 'transactions.direction',
        'status' => 'transactions.status',
        'booked_at' => 'transactions.booked_at',
        'value_date' => 'transactions.value_date',
    ];

    private const array MEASURE_COLUMNS = [
        'transaction_count' => 'transactions.id',
        'total_amount' => 'transactions.amount',
    ];

    public function dataset(): DatasetKey
    {
        return DatasetKey::TRANSACTIONS;
    }

    public function baseTable(): string
    {
        return 'transactions';
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

    public function measureColumns(): array
    {
        return self::MEASURE_COLUMNS;
    }

    public function measureColumn(string $measure): string
    {
        return self::MEASURE_COLUMNS[$measure]
            ?? throw UnknownMeasure::forDataset(
                $this->dataset(),
                $measure,
            );
    }
}
