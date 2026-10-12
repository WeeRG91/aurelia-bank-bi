<?php

namespace App\Analytics\Warehouse\Dimensions;

use DateTimeImmutable;

final readonly class AccountDimensionLoader
{
    private const int BATCH_SIZE = 500;

    public function __construct(
        private AccountDimensionSource $source,
        private AccountDimensionRowFactory $rowFactory,
        private AccountDimensionWriter $writer,
    ) {}

    public function load(DateTimeImmutable $loadedAt): int
    {
        return $this->writer->transaction(
            function () use ($loadedAt): int {
                $batch = [];
                $loadedRows = 0;

                foreach ($this->source->rows() as $sourceRow) {
                    $batch[] = $this->rowFactory->create(
                        $sourceRow,
                        $loadedAt,
                    );

                    if (count($batch) >= self::BATCH_SIZE) {
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

        $this->writer->upsert($batch);

        $batch = [];

        return $rowCount;
    }
}
