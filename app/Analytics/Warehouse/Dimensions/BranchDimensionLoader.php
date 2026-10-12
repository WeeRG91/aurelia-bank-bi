<?php

namespace App\Analytics\Warehouse\Dimensions;

use DateTimeImmutable;

final readonly class BranchDimensionLoader
{
    private const int BATCH_SIZE = 500;

    public function __construct(
        private BranchDimensionSource $source,
        private BranchDimensionRowFactory $rowFactory,
        private BranchDimensionWriter $writer,
    ) {}

    public function load(DateTimeImmutable $loadedAt): int
    {
        return $this->writer->transaction(
            function () use ($loadedAt): int {
                $batch = [];
                $loadedRows = 0;

                foreach ($this->source->rows() as $sourceRow) {
                    $batch[] = $this->rowFactory->create($sourceRow, $loadedAt);

                    if (count($batch) === self::BATCH_SIZE) {
                        $loadedRows += $this->flush($batch);
                    }
                }

                return $loadedRows + $this->flush($batch);
            }
        );
    }

    private function flush(array &$batch): int
    {
        if ($batch === []) {
            return 0;
        }

        $rowCount = count($batch);

        $this->writer->synchronize($batch);

        $batch = [];

        return $rowCount;
    }
}
