<?php

namespace App\Analytics\Datasets;

use InvalidArgumentException;

final class UnknownMeasure extends InvalidArgumentException
{
    public static function forDataset(
        DatasetKey $dataset,
        string $measure,
    ): self {
        return new self(
            "Unknown measure [{$measure}] for dataset [{$dataset->value}].",
        );
    }
}
