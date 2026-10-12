<?php

namespace App\Analytics\Warehouse\Facts;

use Illuminate\Database\DatabaseManager;
use Throwable;

final readonly class DatabaseTransactionFactWriter implements TransactionFactWriter
{
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
            ->transaction($callback);
    }

    public function upsert(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $this->database
            ->connection('warehouse')
            ->table('fact_transactions')
            ->upsert(
                $rows,
                ['source_transaction_id'],
                [
                    'account_key',
                    'branch_key',
                    'booking_date_key',
                    'value_date_key',
                    'transaction_reference',
                    'transaction_type',
                    'category',
                    'currency',
                    'direction',
                    'transaction_status',
                    'absolute_amount',
                    'signed_amount',
                    'source_updated_at',
                    'loaded_at',
                ],
            );
    }
}
