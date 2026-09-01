<?php

namespace App\Analytics\Datasets;

enum SensitivityLevel: string
{
    case INTERNAL = 'internal';
    case CONFIDENTIAL = 'confidential';
    case RESTRICTED = 'restricted';
}
