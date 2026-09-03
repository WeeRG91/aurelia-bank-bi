<?php

namespace App\Analytics\Exports;

enum ExportFormat: string
{
    case CSV = 'csv';
    case XLSX = 'xlsx';

    public function extension(): string
    {
        return $this->value;
    }

    public function contentType(): string
    {
        return match ($this) {
            self::CSV => 'text/csv; charset=UTF-8',
            self::XLSX => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
    }
}
