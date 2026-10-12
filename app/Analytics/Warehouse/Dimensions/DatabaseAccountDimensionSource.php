<?php

namespace App\Analytics\Warehouse\Dimensions;

use Illuminate\Database\DatabaseManager;

final readonly class DatabaseAccountDimensionSource implements AccountDimensionSource
{
    public function __construct(
        private DatabaseManager $database,
    ) {}

    public function rows(): iterable
    {
        $rows = $this->database
            ->connection('warehouse')
            ->table('stg_transactions')
            ->select([
                'source_account_id',
                'account_number',
                'account_type',
                'account_currency',
                'account_opened_at',
                'account_closed_at',
                'account_status',
            ])
            ->distinct()
            ->orderBy('source_account_id')
            ->cursor();

        foreach ($rows as $row) {
            yield [
                'source_account_id' => (int) $row->source_account_id,
                'account_number' => (string) $row->account_number,
                'account_type' => (string) $row->account_type,
                'account_currency' => (string) $row->account_currency,
                'account_opened_at' => (string) $row->account_opened_at,
                'account_closed_at' => $row->account_closed_at === null
                    ? null
                    : (string) $row->account_closed_at,
                'account_status' => (string) $row->account_status,
            ];
        }
    }
}
