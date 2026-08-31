<?php

namespace App\Enums;

enum TransactionDirection: string
{
    case INCOMING = 'incoming';
    case OUTGOING = 'outgoing';
}
