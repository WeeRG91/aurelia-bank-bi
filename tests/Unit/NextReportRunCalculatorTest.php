<?php

namespace Tests\Unit;

use App\Analytics\Scheduling\NextReportRunCalculator;
use App\Analytics\Scheduling\ReportScheduleFrequency;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class NextReportRunCalculatorTest extends TestCase
{
    public function test_daily_run_is_calculated_in_reporting_timezone(): void
    {
        $nextRun = (new NextReportRunCalculator)->calculate(
            frequency: ReportScheduleFrequency::DAILY,
            runTime: '10:00',
            timezone: new DateTimeZone('Europe/Luxembourg'),
            after: new DateTimeImmutable(
                '2026-09-08T07:00:00+00:00',
            ),
        );

        $this->assertSame(
            '2026-09-08T08:00:00+00:00',
            $nextRun->toIso8601String(),
        );
    }

    public function test_elapsed_daily_time_moves_to_next_day(): void
    {
        $nextRun = (new NextReportRunCalculator)->calculate(
            frequency: ReportScheduleFrequency::DAILY,
            runTime: '10:00',
            timezone: new DateTimeZone('Europe/Luxembourg'),
            after: new DateTimeImmutable(
                '2026-09-08T08:00:00+00:00',
            ),
        );

        $this->assertSame(
            '2026-09-09T08:00:00+00:00',
            $nextRun->toIso8601String(),
        );
    }

    public function test_weekly_run_uses_iso_weekday(): void
    {
        $nextRun = (new NextReportRunCalculator)->calculate(
            frequency: ReportScheduleFrequency::WEEKLY,
            runTime: '09:00',
            timezone: new DateTimeZone('Europe/Luxembourg'),
            after: new DateTimeImmutable(
                '2026-09-08T10:00:00+00:00',
            ),
            weekday: 5,
        );

        $this->assertSame(
            '2026-09-11T07:00:00+00:00',
            $nextRun->toIso8601String(),
        );
    }

    public function test_monthly_run_uses_selected_day(): void
    {
        $nextRun = (new NextReportRunCalculator)->calculate(
            frequency: ReportScheduleFrequency::MONTHLY,
            runTime: '06:00',
            timezone: new DateTimeZone('Europe/Luxembourg'),
            after: new DateTimeImmutable(
                '2026-09-08T10:00:00+00:00',
            ),
            dayOfMonth: 15,
        );

        $this->assertSame(
            '2026-09-15T04:00:00+00:00',
            $nextRun->toIso8601String(),
        );
    }

    public function test_spring_dst_gap_moves_to_first_valid_local_time(): void
    {
        $nextRun = (new NextReportRunCalculator)->calculate(
            frequency: ReportScheduleFrequency::DAILY,
            runTime: '02:30',
            timezone: new DateTimeZone('Europe/Luxembourg'),
            after: new DateTimeImmutable(
                '2026-03-28T12:00:00+00:00',
            ),
        );

        $this->assertSame(
            '2026-03-29T01:30:00+00:00',
            $nextRun->toIso8601String(),
        );

        $this->assertSame(
            '03:30',
            $nextRun
                ->setTimezone(
                    new DateTimeZone('Europe/Luxembourg'),
                )
                ->format('H:i'),
        );
    }

    public function test_invalid_recurrence_configuration_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new NextReportRunCalculator)->calculate(
            frequency: ReportScheduleFrequency::WEEKLY,
            runTime: '09:00',
            timezone: new DateTimeZone('UTC'),
            after: new DateTimeImmutable,
            weekday: null,
        );
    }

    public function test_invalid_time_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new NextReportRunCalculator)->calculate(
            frequency: ReportScheduleFrequency::DAILY,
            runTime: '25:90',
            timezone: new DateTimeZone('UTC'),
            after: new DateTimeImmutable,
        );
    }
}
