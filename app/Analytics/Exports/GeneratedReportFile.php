<?php

namespace App\Analytics\Exports;

final readonly class GeneratedReportFile
{
    public function __construct(
        public string $contents,
        public int $rowCount,
    ) {}
}
