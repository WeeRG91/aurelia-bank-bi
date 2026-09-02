<?php

namespace App\Analytics\Time;

enum PeriodComparison: string
{
    case PREVIOUS_PERIOD = 'previous_period';
    case PREVIOUS_YEAR = 'previous_year';
}
