<?php

namespace Tests\Unit;

use App\Analytics\Time\DateRange;
use App\Analytics\Time\RelativeDatePreset;
use App\Analytics\Time\RelativeDateRangeResolver;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RelativeDateRangeResolverTest extends TestCase
{
    public function test_relative_date_presets_have_stable_values(): void
    {
        $this->assertSame(
            [
                'today',
                'yesterday',
                'last_7_days',
                'last_30_days',
                'month_to_date',
                'previous_month',
                'quarter_to_date',
                'previous_quarter',
                'year_to_date',
            ],
            array_column(
                RelativeDatePreset::cases(),
                'value',
            ),
        );
    }

    public function test_reporting_timezone_determines_today(): void
    {
        $range = $this->resolve(
            RelativeDatePreset::TODAY,
        );

        $this->assertSame('2026-09-01', $range->startDate);
        $this->assertSame('2026-09-01', $range->endDate);
    }

    public function test_presets_resolve_to_inclusive_calendar_ranges(): void
    {
        $expectations = [
            RelativeDatePreset::YESTERDAY->value => [
                '2026-08-31',
                '2026-08-31',
            ],
            RelativeDatePreset::LAST_7_DAYS->value => [
                '2026-08-26',
                '2026-09-01',
            ],
            RelativeDatePreset::LAST_30_DAYS->value => [
                '2026-08-03',
                '2026-09-01',
            ],
            RelativeDatePreset::MONTH_TO_DATE->value => [
                '2026-09-01',
                '2026-09-01',
            ],
            RelativeDatePreset::PREVIOUS_MONTH->value => [
                '2026-08-01',
                '2026-08-31',
            ],
            RelativeDatePreset::QUARTER_TO_DATE->value => [
                '2026-07-01',
                '2026-09-01',
            ],
            RelativeDatePreset::PREVIOUS_QUARTER->value => [
                '2026-04-01',
                '2026-06-30',
            ],
            RelativeDatePreset::YEAR_TO_DATE->value => [
                '2026-01-01',
                '2026-09-01',
            ],
        ];

        foreach ($expectations as $presetValue => $expected) {
            $range = $this->resolve(
                RelativeDatePreset::from($presetValue),
            );

            $this->assertSame(
                $expected,
                [$range->startDate, $range->endDate],
                "Unexpected range for {$presetValue}.",
            );
        }
    }

    public function test_date_range_rejects_invalid_calendar_date(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid range start date [2026-02-30].',
        );

        new DateRange(
            startDate: '2026-02-30',
            endDate: '2026-03-01',
        );
    }

    public function test_date_range_rejects_reversed_boundaries(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Date range start must not be after its end.',
        );

        new DateRange(
            startDate: '2026-09-02',
            endDate: '2026-09-01',
        );
    }

    private function resolve(
        RelativeDatePreset $preset,
    ): DateRange {
        return (new RelativeDateRangeResolver)->resolve(
            $preset,
            new DateTimeImmutable(
                '2026-08-31T23:30:00+00:00',
            ),
            new DateTimeZone('Europe/Luxembourg'),
        );
    }
}
