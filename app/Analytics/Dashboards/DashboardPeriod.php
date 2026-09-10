<?php

namespace App\Analytics\Dashboards;

use App\Analytics\Time\RelativeDatePreset;

enum DashboardPeriod: string
{
    case LAST_7_DAYS = 'last_7_days';
    case LAST_30_DAYS = 'last_30_days';
    case MONTH_TO_DATE = 'month_to_date';
    case YEAR_TO_DATE = 'year_to_date';

    public static function default(): self
    {
        return self::LAST_30_DAYS;
    }

    public function label(): string
    {
        return match ($this) {
            self::LAST_7_DAYS => 'Last 7 days',
            self::LAST_30_DAYS => 'Last 30 days',
            self::MONTH_TO_DATE => 'Month to date',
            self::YEAR_TO_DATE => 'Year to date',
        };
    }

    public function relativeDatePreset(): RelativeDatePreset
    {
        return RelativeDatePreset::from($this->value);
    }
}
