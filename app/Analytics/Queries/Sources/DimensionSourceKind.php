<?php

namespace App\Analytics\Queries\Sources;

enum DimensionSourceKind: string
{
    case COLUMN = 'column';
    case LOCAL_DATE = 'local_date';
    case LOCAL_MONTH = 'local_month';
    case LOCAL_QUARTER = 'local_quarter';
    case LOCAL_YEAR = 'local_year';
}
