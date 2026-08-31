<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case BOOKED = 'booked';
    case FAILED = 'failed';
    case REVERSED = 'reversed';
}
