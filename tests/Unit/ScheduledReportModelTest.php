<?php

namespace Tests\Unit;

use App\Analytics\Exports\ExportFormat;
use App\Analytics\Scheduling\ReportScheduleFrequency;
use App\Analytics\Scheduling\ScheduledReportStatus;
use App\Models\Employee;
use App\Models\ReportExport;
use App\Models\SavedReport;
use App\Models\ScheduledReport;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

final class ScheduledReportModelTest extends TestCase
{
    public function test_it_casts_schedule_configuration(): void
    {
        $schedule = (new ScheduledReport)->forceFill([
            'format' => 'xlsx',
            'frequency' => 'weekly',
            'run_time' => '09:30:00',
            'timezone' => 'Europe/Luxembourg',
            'weekday' => '1',
            'day_of_month' => null,
            'status' => 'active',
            'next_run_at' => '2026-09-14T07:30:00+00:00',
        ]);

        $this->assertSame(
            ExportFormat::XLSX,
            $schedule->format,
        );

        $this->assertSame(
            ReportScheduleFrequency::WEEKLY,
            $schedule->frequency,
        );

        $this->assertSame(1, $schedule->weekday);

        $this->assertSame(
            ScheduledReportStatus::ACTIVE,
            $schedule->status,
        );

        $this->assertInstanceOf(
            CarbonImmutable::class,
            $schedule->next_run_at,
        );
    }

    public function test_it_defines_schedule_relationships(): void
    {
        $schedule = new ScheduledReport;

        $this->assertInstanceOf(
            BelongsTo::class,
            $schedule->savedReport(),
        );

        $this->assertSame(
            SavedReport::class,
            $schedule->savedReport()->getRelated()::class,
        );

        $this->assertInstanceOf(
            BelongsTo::class,
            $schedule->creator(),
        );

        $this->assertSame(
            Employee::class,
            $schedule->creator()->getRelated()::class,
        );

        $this->assertInstanceOf(
            HasMany::class,
            $schedule->exports(),
        );

        $this->assertSame(
            ReportExport::class,
            $schedule->exports()->getRelated()::class,
        );
    }
}
