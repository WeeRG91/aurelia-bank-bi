<?php

namespace App\Enums;

enum AccountStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case FROZEN = 'frozen';
    case DORMANT = 'dormant';
    case CLOSED = 'closed';
}
