<?php

namespace App\Analytics\Warehouse\Etl;

interface EtlPipeline
{
    public function identifier(): string;

    public function run(): EtlPipelineResult;
}
