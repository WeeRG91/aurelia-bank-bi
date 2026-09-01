<?php

namespace App\Analytics\Queries\Sources;

use InvalidArgumentException;

final readonly class MeasureSource
{
    public function __construct(
        public string $column,
        public MeasureSourceKind $kind = MeasureSourceKind::COLUMN,
        public ?string $directionColumn = null,
    ) {
        $this->assertQualifiedColumn($this->column);

        if ($this->kind === MeasureSourceKind::COLUMN) {
            if ($this->directionColumn !== null) {
                throw new InvalidArgumentException(
                    'Plain column measure source cannot define a direction column.',
                );
            }

            return;
        }

        if ($this->directionColumn === null) {
            throw new InvalidArgumentException(
                'Directional measure source requires a direction column.',
            );
        }

        $this->assertQualifiedColumn($this->directionColumn);
    }

    private function assertQualifiedColumn(string $column): void
    {
        if (
            preg_match(
                '/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/',
                $column,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                "Invalid qualified measure column [{$column}].",
            );
        }
    }
}
