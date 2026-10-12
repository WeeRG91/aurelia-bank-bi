<?php

namespace App\Analytics\Warehouse\Dimensions;

interface BranchDimensionWriter
{
    public function transaction(callable $callback): mixed;

    /**
     * @param  list<array<string, int|string|null>>  $rows
     */
    public function synchronize(array $rows): void;
}
