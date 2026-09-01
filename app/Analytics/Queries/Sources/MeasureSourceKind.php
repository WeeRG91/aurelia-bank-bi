<?php

namespace App\Analytics\Queries\Sources;

enum MeasureSourceKind: string
{
    case COLUMN = 'column';
    case INCOMING_AMOUNT = 'incoming_amount';
    case OUTGOING_AMOUNT = 'outgoing_amount';
    case NET_AMOUNT = 'net_amount';
}
