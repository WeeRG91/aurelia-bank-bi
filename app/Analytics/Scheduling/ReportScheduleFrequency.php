<?php

namespace App\Analytics\Scheduling;

enum ReportScheduleFrequency: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';

    public function requiresWeekday(): bool
    {
        return $this === self::WEEKLY;
    }

    public function requiresDayOfMonth(): bool
    {
        return $this === self::MONTHLY;
    }
}
