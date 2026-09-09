<?php

namespace Tests\Unit;

use App\Analytics\Scheduling\ReportScheduleFrequency;
use App\Analytics\Scheduling\ScheduledReportStatus;
use PHPUnit\Framework\TestCase;

final class ReportSchedulingEnumsTest extends TestCase
{
    public function test_schedule_frequencies_have_stable_values(): void
    {
        $this->assertSame(
            ['daily', 'weekly', 'monthly'],
            array_column(
                ReportScheduleFrequency::cases(),
                'value',
            ),
        );
    }

    public function test_frequency_configuration_requirements_are_explicit(): void
    {
        $this->assertFalse(
            ReportScheduleFrequency::DAILY->requiresWeekday(),
        );
        $this->assertFalse(
            ReportScheduleFrequency::DAILY->requiresDayOfMonth(),
        );

        $this->assertTrue(
            ReportScheduleFrequency::WEEKLY->requiresWeekday(),
        );
        $this->assertFalse(
            ReportScheduleFrequency::WEEKLY->requiresDayOfMonth(),
        );

        $this->assertFalse(
            ReportScheduleFrequency::MONTHLY->requiresWeekday(),
        );
        $this->assertTrue(
            ReportScheduleFrequency::MONTHLY->requiresDayOfMonth(),
        );
    }

    public function test_only_active_schedules_can_dispatch(): void
    {
        $this->assertTrue(
            ScheduledReportStatus::ACTIVE->canDispatch(),
        );

        $this->assertFalse(
            ScheduledReportStatus::PAUSED->canDispatch(),
        );
    }
}
