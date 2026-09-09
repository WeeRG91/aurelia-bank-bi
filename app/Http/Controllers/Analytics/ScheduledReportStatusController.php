<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Analytics\Scheduling\NextReportRunCalculator;
use App\Analytics\Scheduling\ScheduledReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\ScheduledReport;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateInvalidTimeZoneException;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use LogicException;
use Throwable;

final class ScheduledReportStatusController extends Controller
{
    /**
     * @throws Throwable
     */
    public function pause(
        Request $request,
        ScheduledReport $scheduledReport,
        WebAuditRecorder $audit,
    ): RedirectResponse {
        Gate::authorize('update', $scheduledReport);

        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        if (! $employee instanceof Employee) {
            throw new LogicException(
                'The authenticated user has no employee profile.',
            );
        }

        $savedReport = $scheduledReport->savedReport;

        DB::transaction(
            function () use (
                $request,
                $scheduledReport,
                $savedReport,
                $employee,
                $audit,
            ): void {
                $scheduledReport->update([
                    'status' => ScheduledReportStatus::PAUSED,
                ]);

                $audit->record(
                    request: $request,
                    action: AuditAction::SCHEDULE_PAUSED,
                    outcome: AuditOutcome::SUCCEEDED,
                    actor: $employee,
                    dataset: $savedReport?->dataset,
                    subjectType: AuditSubjectType::SCHEDULED_REPORT,
                    subjectId: $scheduledReport->getKey(),
                    context: AuditContext::from([
                        'definition_version' => $savedReport
                            ?->definition_version,
                        'format' => $scheduledReport->format->value,
                        'frequency' => $scheduledReport
                            ->frequency
                            ->value,
                        'status_from' => ScheduledReportStatus::ACTIVE->value,
                        'status_to' => ScheduledReportStatus::PAUSED->value,
                    ]),
                );
            },
        );

        return to_route('analytics.scheduled-reports.index')
            ->with('status', 'Scheduled report paused.');
    }

    /**
     * @throws DateInvalidTimeZoneException
     * @throws Throwable
     */
    public function resume(
        Request $request,
        ScheduledReport $scheduledReport,
        NextReportRunCalculator $calculator,
        WebAuditRecorder $audit,
    ): RedirectResponse {
        Gate::authorize('update', $scheduledReport);

        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        if (! $employee instanceof Employee) {
            throw new LogicException(
                'The authenticated user has no employee profile.',
            );
        }

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

        DB::transaction(
            function () use (
                $request,
                $scheduledReport,
                $savedReport,
                $employee,
                $nextRunAt,
                $audit,
            ): void {
                $scheduledReport->update([
                    'status' => ScheduledReportStatus::ACTIVE,
                    'next_run_at' => $nextRunAt,
                ]);

                $audit->record(
                    request: $request,
                    action: AuditAction::SCHEDULE_RESUMED,
                    outcome: AuditOutcome::SUCCEEDED,
                    actor: $employee,
                    dataset: $savedReport->dataset,
                    subjectType: AuditSubjectType::SCHEDULED_REPORT,
                    subjectId: $scheduledReport->getKey(),
                    context: AuditContext::from([
                        'definition_version' => $savedReport
                            ->definition_version,
                        'format' => $scheduledReport->format->value,
                        'frequency' => $scheduledReport
                            ->frequency
                            ->value,
                        'status_from' => ScheduledReportStatus::PAUSED->value,
                        'status_to' => ScheduledReportStatus::ACTIVE->value,
                    ]),
                );
            },
        );

        return to_route('analytics.scheduled-reports.index')
            ->with('status', 'Scheduled report resumed.');
    }
}
