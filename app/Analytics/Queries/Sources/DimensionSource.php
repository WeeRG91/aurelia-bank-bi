<?php

namespace App\Analytics\Queries\Sources;

use InvalidArgumentException;

final readonly class DimensionSource
{
    public function __construct(
        public string $column,
        public DimensionSourceKind $kind = DimensionSourceKind::COLUMN,
    ) {
        if (
            preg_match(
                '/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/',
                $this->column,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                "Invalid qualified dimension column [{$this->column}].",
            );
        }
    }
}
