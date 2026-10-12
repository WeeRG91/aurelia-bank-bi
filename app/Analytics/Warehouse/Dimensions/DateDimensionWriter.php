<?php

namespace App\Analytics\Warehouse\Dimensions;

interface DateDimensionWriter
{
    /**
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    public function transaction(callable $callback): mixed;

    /**
     * @param  list<array<string, int|string|bool>>  $rows
     */
    public function upsert(array $rows): void;
}
