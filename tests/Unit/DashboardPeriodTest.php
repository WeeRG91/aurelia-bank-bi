<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardPeriod;
use App\Analytics\Time\RelativeDatePreset;
use PHPUnit\Framework\TestCase;

final class DashboardPeriodTest extends TestCase
{
    public function test_dashboard_periods_have_stable_values(): void
    {
        $this->assertSame(
            [
                'last_7_days',
                'last_30_days',
                'month_to_date',
                'year_to_date',
            ],
            array_column(DashboardPeriod::cases(), 'value'),
        );
    }

    public function test_last_thirty_days_is_the_default(): void
    {
        $this->assertSame(
            DashboardPeriod::LAST_30_DAYS,
            DashboardPeriod::default(),
        );
    }

    public function test_periods_map_to_relative_date_presets(): void
    {
        foreach (DashboardPeriod::cases() as $period) {
            $this->assertSame(
                RelativeDatePreset::from($period->value),
                $period->relativeDatePreset(),
            );

            $this->assertNotSame('', trim($period->label()));
        }
    }
}
