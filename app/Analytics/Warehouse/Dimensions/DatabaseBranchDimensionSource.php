<?php

namespace App\Analytics\Warehouse\Dimensions;

use Illuminate\Database\DatabaseManager;

final readonly class DatabaseBranchDimensionSource implements BranchDimensionSource
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
                'source_branch_id',
                'branch_code',
                'branch_name',
                'branch_country_code',
                'branch_city',
            ])
            ->distinct()
            ->orderBy('source_branch_id')
            ->cursor();

        foreach ($rows as $row) {
            yield [
                'source_branch_id' => (int) $row->source_branch_id,
                'branch_code' => (string) $row->branch_code,
                'branch_name' => (string) $row->branch_name,
                'branch_country_code' => (string) $row->branch_country_code,
                'branch_city' => (string) $row->branch_city,
            ];
        }
    }
}
