<?php

namespace App\Analytics\Warehouse\Etl;

interface TransactionStagingSource
{
    /**
     * @return iterable<object>
     */
    public function extract(): iterable;
}
