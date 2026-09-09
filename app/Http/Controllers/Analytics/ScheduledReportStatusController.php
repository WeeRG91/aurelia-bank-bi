<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Scheduling\NextReportRunCalculator;
use App\Analytics\Scheduling\ScheduledReportStatus;
use App\Http\Controllers\Controller;
use App\Models\SavedReport;
use App\Models\ScheduledReport;
use Carbon\CarbonImmutable;
use DateInvalidTimeZoneException;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ScheduledReportStatusController extends Controller
{
    public function pause(
        ScheduledReport $scheduledReport,
    ): RedirectResponse {
        Gate::authorize('update', $scheduledReport);

        $scheduledReport->update([
            'status' => ScheduledReportStatus::PAUSED,
        ]);

        return to_route('analytics.scheduled-reports.index')
            ->with('status', 'Scheduled report paused.');
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function resume(
        ScheduledReport $scheduledReport,
        NextReportRunCalculator $calculator,
    ): RedirectResponse {
        Gate::authorize('update', $scheduledReport);

        $savedReport = $scheduledReport->savedReport;

        if (! $savedReport instanceof SavedReport) {
            return to_route('analytics.scheduled-reports.index')
                ->with(
                    'error',
                    'This schedule cannot be resumed because its report is unavailable.',
                );
        }

        Gate::authorize('schedule', $savedReport);

        $nextRunAt = $calculator->calculate(
            frequency: $scheduledReport->frequency,
            runTime: (string) $scheduledReport->run_time,
            timezone: new DateTimeZone($scheduledReport->timezone),
            after: CarbonImmutable::now('UTC'),
            weekday: $scheduledReport->weekday,
            dayOfMonth: $scheduledReport->day_of_month,
        );

        $scheduledReport->update([
            'status' => ScheduledReportStatus::ACTIVE,
            'next_run_at' => $nextRunAt,
        ]);

        return to_route('analytics.scheduled-reports.index')
            ->with('status', 'Scheduled report resumed.');
    }
}
