<?php

namespace App\Analytics\Datasets;

use InvalidArgumentException;

final class UnknownDataset extends InvalidArgumentException
{
    public static function forIdentifier(
        DatasetKey|string $identifier,
    ): self {
        $value = $identifier instanceof DatasetKey
            ? $identifier->value
            : $identifier;

        return new self(
            "Unknown analytics dataset [$value].",
        );
    }
}
