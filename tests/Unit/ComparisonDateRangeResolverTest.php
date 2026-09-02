<?php

namespace Tests\Unit;

use App\Analytics\Time\ComparisonDateRangeResolver;
use App\Analytics\Time\DateRange;
use App\Analytics\Time\PeriodComparison;
use PHPUnit\Framework\TestCase;

class ComparisonDateRangeResolverTest extends TestCase
{
    public function test_comparison_values_are_stable(): void
    {
        $this->assertSame(
            [
                'previous_period',
                'previous_year',
            ],
            array_column(PeriodComparison::cases(), 'value'),
        );
    }

    public function test_previous_period_has_the_same_inclusive_length(): void
    {
        $comparison = (new ComparisonDateRangeResolver)->resolve(
            new DateRange(
                startDate: '2026-08-01',
                endDate: '2026-08-31',
            ),
            PeriodComparison::PREVIOUS_PERIOD,
        );

        $this->assertSame(
            '2026-07-01',
            $comparison->startDate,
        );

        $this->assertSame(
            '2026-07-31',
            $comparison->endDate,
        );
    }

    public function test_rolling_seven_day_period_uses_previous_seven_days(): void
    {
        $comparison = (new ComparisonDateRangeResolver)->resolve(
            new DateRange(
                startDate: '2026-08-25',
                endDate: '2026-08-31',
            ),
            PeriodComparison::PREVIOUS_PERIOD,
        );

        $this->assertSame(
            '2026-08-18',
            $comparison->startDate,
        );

        $this->assertSame(
            '2026-08-24',
            $comparison->endDate,
        );
    }

    public function test_previous_year_preserves_calendar_boundaries(): void
    {
        $comparison = (new ComparisonDateRangeResolver)->resolve(
            new DateRange(
                startDate: '2026-01-01',
                endDate: '2026-08-31',
            ),
            PeriodComparison::PREVIOUS_YEAR,
        );

        $this->assertSame(
            '2025-01-01',
            $comparison->startDate,
        );

        $this->assertSame(
            '2025-08-31',
            $comparison->endDate,
        );
    }

    public function test_previous_year_handles_leap_day_without_overflow(): void
    {
        $comparison = (new ComparisonDateRangeResolver)->resolve(
            new DateRange(
                startDate: '2024-02-29',
                endDate: '2024-02-29',
            ),
            PeriodComparison::PREVIOUS_YEAR,
        );

        $this->assertSame(
            '2023-02-28',
            $comparison->startDate,
        );

        $this->assertSame(
            '2023-02-28',
            $comparison->endDate,
        );
    }
}
