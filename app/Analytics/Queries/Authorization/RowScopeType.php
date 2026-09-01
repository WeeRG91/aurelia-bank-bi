<?php

namespace App\Analytics\Queries\Authorization;

enum RowScopeType: string
{
    case DENIED = 'denied';
    case UNRESTRICTED = 'unrestricted';
    case BRANCH = 'branch';
}
