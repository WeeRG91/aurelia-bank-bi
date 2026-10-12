<?php

namespace App\Analytics\Warehouse\Dimensions;

use DateTimeImmutable;

final class DateDimensionRowFactory
{
    /**
     * @return array{
     *     date_key: int,
     *     calendar_date: string,
     *     calendar_year: int,
     *     calendar_quarter: int,
     *     calendar_month: int,
     *     month_name: string,
     *     iso_year: int,
     *     iso_week: int,
     *     day_of_month: int,
     *     iso_weekday: int,
     *     is_weekend: bool
     * }
     */
    public function create(DateTimeImmutable $date): array
    {
        $month = (int) $date->format('n');
        $isoWeekday = (int) $date->format('N');

        return [
            'date_key' => (int) $date->format('Ymd'),
            'calendar_date' => $date->format('Y-m-d'),
            'calendar_year' => (int) $date->format('Y'),
            'calendar_quarter' => (int) ceil($month / 3),
            'calendar_month' => $month,
            'month_name' => $date->format('F'),
            'iso_year' => (int) $date->format('o'),
            'iso_week' => (int) $date->format('W'),
            'day_of_month' => (int) $date->format('j'),
            'iso_weekday' => $isoWeekday,
            'is_weekend' => $isoWeekday >= 6,
        ];
    }
}
