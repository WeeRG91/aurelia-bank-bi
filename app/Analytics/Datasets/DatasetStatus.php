<?php

namespace App\Analytics\Datasets;

enum DatasetStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case DEPRECATED = 'deprecated';
}
