<?php

namespace App\Analytics\Warehouse\Dimensions;

use Illuminate\Database\DatabaseManager;
use Throwable;

final readonly class DatabaseBranchDimensionWriter implements BranchDimensionWriter
{
    /**
     * @var list<string>
     */
    private const array UPDATE_COLUMNS = [
        'branch_code',
        'branch_name',
        'country_code',
        'city',
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
                static fn (): mixed => $callback(),
            );
    }

    public function upsert(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $this->database
            ->connection('warehouse')
            ->table('dim_branch')
            ->upsert(
                $rows,
                ['source_branch_id'],
                self::UPDATE_COLUMNS,
            );
    }
}
