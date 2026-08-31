<?php

namespace App\Enums;

enum CustomerSegment: string
{
    case RETAIL = 'retail';
    case PREMIUM = 'premium';
    case PRIVATE_BANKING = 'private_banking';
    case BUSINESS = 'business';
}
