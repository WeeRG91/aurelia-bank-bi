<?php

namespace App\Analytics\Queries\Sources;

enum JoinType: string
{
    case INNER = 'INNER JOIN';
    case LEFT = 'LEFT JOIN';
}
