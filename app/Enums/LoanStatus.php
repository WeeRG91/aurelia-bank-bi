<?php

namespace App\Enums;

enum LoanStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case PAID = 'paid';
    case DEFAULTED = 'defaulted';
    case CANCELLED = 'cancelled';
}
