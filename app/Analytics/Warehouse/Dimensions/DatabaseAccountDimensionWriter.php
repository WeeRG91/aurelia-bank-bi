<?php

namespace App\Analytics\Warehouse\Dimensions;

use Illuminate\Database\DatabaseManager;
use Throwable;

final readonly class DatabaseAccountDimensionWriter implements AccountDimensionWriter
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
            ->table('dim_account')
            ->upsert(
                $rows,
                ['source_account_id'],
                [
                    'account_number',
                    'account_type',
                    'currency',
                    'opened_at',
                    'closed_at',
                    'status',
                    'source_updated_at',
                    'loaded_at',
                ],
            );
    }
}
