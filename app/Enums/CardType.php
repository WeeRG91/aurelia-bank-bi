<?php

namespace App\Enums;

enum CardType: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';
    case PREPAID = 'prepaid';
}
