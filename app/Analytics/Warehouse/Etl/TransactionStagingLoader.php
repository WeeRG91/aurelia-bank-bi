<?php

namespace App\Analytics\Warehouse\Etl;

interface TransactionStagingLoader
{
    /**
     * @param  iterable<object>  $sourceRows
     */
    public function load(iterable $sourceRows): EtlPipelineResult;
}
