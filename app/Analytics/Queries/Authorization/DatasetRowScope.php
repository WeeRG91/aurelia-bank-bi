<?php

namespace App\Analytics\Queries\Authorization;

use InvalidArgumentException;

final readonly class DatasetRowScope
{
    private function __construct(
        public RowScopeType $type,
        public ?int $branchId
    ) {}

    public static function denied(): self
    {
        return new self(
            RowScopeType::DENIED,
            null,
        );
    }

    public static function unrestricted(): self
    {
        return new self(
            RowScopeType::UNRESTRICTED,
            null,
        );
    }

    public static function branch(int $branchId): self
    {
        if ($branchId < 1) {
            throw new InvalidArgumentException(
                'Branch scope requires a positive branch identifier.',
            );
        }

        return new self(
            RowScopeType::BRANCH,
            $branchId,
        );
    }
}
