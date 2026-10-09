<?php

namespace App\Analytics\Warehouse\Etl;

interface TransactionStagingBatchWriter
{
    /**
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    public function transaction(callable $callback): mixed;

    /**
     * @param  list<array<string, int|string|null>>  $rows
     */
    public function upsert(array $rows): void;
}
