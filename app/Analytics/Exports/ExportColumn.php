<?php

namespace App\Analytics\Exports;

use App\Analytics\Datasets\FieldDataType;
use InvalidArgumentException;

final readonly class ExportColumn
{
    public function __construct(
        public string $key,
        public string $label,
        public FieldDataType $dataType,
    ) {
        if (preg_match('/^[a-z][a-z0-9_]*$/', $this->key) !== 1) {
            throw new InvalidArgumentException(
                "Invalid export column key [{$this->key}].",
            );
        }

        if (trim($this->label) === '') {
            throw new InvalidArgumentException(
                'Export column label must not be blank.',
            );
        }
    }
}
