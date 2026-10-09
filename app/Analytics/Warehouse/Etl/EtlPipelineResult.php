<?php

namespace App\Analytics\Warehouse\Etl;

use InvalidArgumentException;

final readonly class EtlPipelineResult
{
    public function __construct(
        public int $extractedRows,
        public int $loadedRows,
    ) {
        if (
            $this->extractedRows < 0
            || $this->loadedRows < 0
            || $this->loadedRows > $this->extractedRows
        ) {
            throw new InvalidArgumentException(
                'ETL result counts must be non-negative and loaded rows cannot exceed extracted rows.',
            );
        }
    }
}
