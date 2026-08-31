<?php

namespace App\Enums;

enum CardTransactionStatus: string
{
    case PENDING = 'pending';
    case AUTHORIZED = 'authorized';
    case DECLINED = 'declined';
    case SETTLED = 'settled';
    case REVERSED = 'reversed';
}
