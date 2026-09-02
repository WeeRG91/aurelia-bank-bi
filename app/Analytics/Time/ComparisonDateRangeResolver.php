<?php

namespace App\Analytics\Time;

use Carbon\CarbonImmutable;

final class ComparisonDateRangeResolver
{
    public function resolve(
        DateRange $current,
        PeriodComparison $comparison,
    ): DateRange {
        $start = CarbonImmutable::parse(
            $current->startDate,
            'UTC',
        )->startOfDay();

        $end = CarbonImmutable::parse(
            $current->endDate,
            'UTC',
        )->startOfDay();

        [$comparisonStart, $comparisonEnd] = match ($comparison) {
            PeriodComparison::PREVIOUS_PERIOD => [
                $start->subDays(
                    (int) $start->diffInDays($end) + 1,
                ),
                $start->subDay(),
            ],

            PeriodComparison::PREVIOUS_YEAR => [
                $start->subYearNoOverflow(),
                $end->subYearNoOverflow(),
            ],
        };

        return new DateRange(
            startDate: $comparisonStart->toDateString(),
            endDate: $comparisonEnd->toDateString(),
        );
    }
}
