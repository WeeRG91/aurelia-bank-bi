<?php

namespace App\Analytics\Time;

enum RelativeDatePreset: string
{
    case TODAY = 'today';
    case YESTERDAY = 'yesterday';
    case LAST_7_DAYS = 'last_7_days';
    case LAST_30_DAYS = 'last_30_days';
    case MONTH_TO_DATE = 'month_to_date';
    case PREVIOUS_MONTH = 'previous_month';
    case QUARTER_TO_DATE = 'quarter_to_date';
    case PREVIOUS_QUARTER = 'previous_quarter';
    case YEAR_TO_DATE = 'year_to_date';
}
