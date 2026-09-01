<?php

namespace App\Analytics\Datasets;

use InvalidArgumentException;

final class UnknownDimension extends InvalidArgumentException
{
    public static function forDataset(
        DatasetKey $dataset,
        string $dimension,
    ): self {
        return new self(
            "Unknown dimension [$dimension] for dataset [$dataset->value].",
        );
    }
}
