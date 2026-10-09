<?php

namespace App\Analytics\Warehouse\Etl;

final readonly class TransactionStagingPipeline implements EtlPipeline
{
    public function __construct(
        private TransactionStagingSource $source,
        private TransactionStagingLoader $loader,
    ) {}

    public function identifier(): string
    {
        return 'transactions_staging';
    }

    public function run(): EtlPipelineResult
    {
        return $this->loader->load(
            $this->source->extract(),
        );
    }
}
