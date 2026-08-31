<?php

namespace App\Enums;

enum LoanType: string
{
    case PERSONAL = 'personal';
    case MORTGAGE = 'mortgage';
    case AUTO = 'auto';
    case BUSINESS = 'business';
}
