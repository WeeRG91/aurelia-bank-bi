<?php

namespace App\Analytics\Warehouse\Etl;

use Illuminate\Database\DatabaseManager;
use Throwable;

final readonly class DatabaseTransactionStagingBatchWriter implements TransactionStagingBatchWriter
{
    /**
     * @var list<string>
     */
    private const array UPDATE_COLUMNS = [
        'source_account_id',
        'source_branch_id',
        'transaction_reference',
        'transaction_type',
        'category',
        'amount',
        'transaction_currency',
        'direction',
        'booked_at',
        'value_date',
        'transaction_status',
        'account_number',
        'account_type',
        'account_currency',
        'account_opened_at',
        'account_closed_at',
        'account_status',
        'branch_code',
        'branch_name',
        'branch_country_code',
        'branch_city',
        'source_updated_at',
        'loaded_at',
    ];

    public function __construct(
        private DatabaseManager $database,
    ) {}

    /**
     * @throws Throwable
     */
    public function transaction(callable $callback): mixed
    {
        return $this->database
            ->connection('warehouse')
            ->transaction(
                static fn (): mixed => $callback()
            );
    }

    public function upsert(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $this->database
            ->connection('warehouse')
            ->table('stg_transactions')
            ->upsert(
                $rows,
                ['source_transaction_id'],
                self::UPDATE_COLUMNS,
            );
    }
}
