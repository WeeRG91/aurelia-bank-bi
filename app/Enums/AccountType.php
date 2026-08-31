<?php

namespace App\Enums;

enum AccountType: string
{
    case CURRENT = 'current';
    case SAVINGS = 'savings';
    case TERM_DEPOSIT = 'term_deposit';
}
