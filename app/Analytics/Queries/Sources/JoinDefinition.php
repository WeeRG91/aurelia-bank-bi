<?php

namespace App\Analytics\Queries\Sources;

use InvalidArgumentException;

final readonly class JoinDefinition
{
    public function __construct(
        public string $table,
        public string $leftColumn,
        public string $rightColumn,
    ) {
        if (preg_match('/^[a-z][a-z0-9_]*$/', $this->table) !== 1) {
            throw new InvalidArgumentException(
                "Invalid join table [{$this->table}].",
            );
        }

        foreach ([$this->leftColumn, $this->rightColumn] as $column) {
            if (
                preg_match(
                    '/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/',
                    $column,
                ) !== 1
            ) {
                throw new InvalidArgumentException(
                    "Invalid qualified column [{$column}].",
                );
            }
        }

        if (! str_starts_with($this->rightColumn, "{$this->table}.")) {
            throw new InvalidArgumentException(
                "Join column [{$this->rightColumn}] must belong to table [{$this->table}].",
            );
        }
    }
}
