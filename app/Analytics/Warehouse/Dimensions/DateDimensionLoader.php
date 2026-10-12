<?php

namespace App\Analytics\Warehouse\Dimensions;

use DateTimeImmutable;

final readonly class DateDimensionLoader
{
    private const int BATCH_SIZE = 500;

    public function __construct(
        private DateDimensionRangeGenerator $generator,
        private DateDimensionWriter $writer,
    ) {}

    public function load(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
    ): int {
        return $this->writer->transaction(
            function () use ($start, $end): int {
                $batch = [];
                $loadedRows = 0;

                foreach ($this->generator->generate($start, $end) as $row) {
                    $batch[] = $row;

                    if (count($batch) >= self::BATCH_SIZE) {
                        $loadedRows += $this->flush($batch);
                    }
                }

                $loadedRows += $this->flush($batch);

                return $loadedRows;
            }
        );
    }

    /**
     * @param  list<array<string, int|string|bool>>  $batch
     */
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
