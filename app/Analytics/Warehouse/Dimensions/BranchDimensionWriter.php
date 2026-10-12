<?php

namespace App\Analytics\Warehouse\Dimensions;

interface BranchDimensionWriter
{
    public function transaction(callable $callback): mixed;

    /**
     * @param  list<array<string, int|string|null>>  $rows
     */
    public function upsert(array $rows): void;
}
