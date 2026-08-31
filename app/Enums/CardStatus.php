<?php

namespace App\Enums;

enum CardStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
}
