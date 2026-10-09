<?php

namespace App\Analytics\Warehouse\Etl;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;

final readonly class DatabaseTransactionStagingSource implements TransactionStagingSource
{
    public function __construct(
        private DatabaseManager $database,
    ) {}

    public function extract(): iterable
    {
        return $this->query()->cursor();
    }

    public function query(): Builder
    {
        return $this->database
            ->connection('pgsql')
            ->table('transactions')
            ->join(
                'accounts',
                'transactions.account_id',
                '=',
                'accounts.id',
            )
            ->join(
                'branches',
                'accounts.branch_id',
                '=',
                'branches.id',
            )
            ->select([
                'transactions.id as source_transaction_id',
                'accounts.id as source_account_id',
                'branches.id as source_branch_id',

                'transactions.transaction_reference',
                'transactions.transaction_type',
                'transactions.category',
                'transactions.amount',
                'transactions.currency as transaction_currency',
                'transactions.direction',
                'transactions.booked_at',
                'transactions.value_date',
                'transactions.status as transaction_status',

                'accounts.account_number',
                'accounts.account_type',
                'accounts.currency as account_currency',
                'accounts.opened_at as account_opened_at',
                'accounts.closed_at as account_closed_at',
                'accounts.status as account_status',

                'branches.branch_code',
                'branches.name as branch_name',
                'branches.country_code as branch_country_code',
                'branches.city as branch_city',
            ])
            ->selectRaw(
                <<<'SQL'
                    GREATEST(
                        transactions.updated_at,
                        accounts.updated_at,
                        branches.updated_at
                    ) AS source_updated_at
                SQL,
            )
            ->orderBy('transactions.id');
    }
}
