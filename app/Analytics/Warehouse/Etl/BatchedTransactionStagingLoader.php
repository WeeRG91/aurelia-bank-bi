<?php

namespace App\Analytics\Warehouse\Etl;

use Carbon\CarbonImmutable;

final readonly class BatchedTransactionStagingLoader implements TransactionStagingLoader
{
    private const int BATCH_SIZE = 500;

    public function __construct(
        private TransactionStagingTransformer $transformer,
        private TransactionStagingBatchWriter $writer,
    ) {}

    public function load(iterable $sourceRows): EtlPipelineResult
    {
        $loadedAt = CarbonImmutable::now('UTC');

        return $this->writer->transaction(
            function () use ($sourceRows, $loadedAt): EtlPipelineResult {
                $batch = [];
                $extractedRows = 0;
                $loadedRows = 0;

                foreach ($sourceRows as $sourceRow) {
                    $extractedRows++;

                    $batch[] = $this->transformer->transform($sourceRow, $loadedAt);

                    if (count($batch) === self::BATCH_SIZE) {
                        $loadedRows += $this->flush($batch);
                    }
                }

                $loadedRows += $this->flush($batch);

                return new EtlPipelineResult(
                    extractedRows: $extractedRows,
                    loadedRows: $loadedRows,
                );
            },
        );
    }

    private function flush(array &$batch): int
    {
        if ($batch === []) {
            return 0;
        }

        $rowCount = count($batch);

        $this->writer->upsert($batch);

        $batch = [];

        return $rowCount;
    }
}
