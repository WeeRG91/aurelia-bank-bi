<?php

namespace App\Analytics\Time;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;

final class RelativeDateRangeResolver
{
    public function resolve(
        RelativeDatePreset $preset,
        DateTimeImmutable $now,
        DateTimeZone $reportingTimeZone,
    ): DateRange {
        $today = CarbonImmutable::instance($now)
            ->setTimezone($reportingTimeZone)
            ->startOfDay();

        [$start, $end] = match ($preset) {
            RelativeDatePreset::TODAY => [
                $today,
                $today,
            ],
            RelativeDatePreset::YESTERDAY => [
                $today->subDay(),
                $today->subDay(),
            ],
            RelativeDatePreset::LAST_7_DAYS => [
                $today->subDays(6),
                $today,
            ],
            RelativeDatePreset::LAST_30_DAYS => [
                $today->subDays(29),
                $today,
            ],
            RelativeDatePreset::MONTH_TO_DATE => [
                $today->startOfMonth(),
                $today,
            ],
            RelativeDatePreset::PREVIOUS_MONTH => [
                $today->subMonthNoOverflow()->startOfMonth(),
                $today->subMonthNoOverflow()->endOfMonth(),
            ],
            RelativeDatePreset::QUARTER_TO_DATE => [
                $today->startOfQuarter(),
                $today,
            ],
            RelativeDatePreset::PREVIOUS_QUARTER => [
                $today->startOfQuarter()
                    ->subDay()
                    ->startOfQuarter(),
                $today->startOfQuarter()->subDay(),
            ],
            RelativeDatePreset::YEAR_TO_DATE => [
                $today->startOfYear(),
                $today,
            ],
        };

        return new DateRange(
            startDate: $start->toDateString(),
            endDate: $end->toDateString(),
        );
    }
}
