<?php

namespace App\Analytics\Datasets;

enum DimensionKind: string
{
    case IDENTIFIER = 'identifier';
    case CATEGORICAL = 'categorical';
    case TEMPORAL = 'temporal';
}
