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
use App\Http\Requests\Analytics\StoreScheduledReportRequest;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\ScheduledReport;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateInvalidTimeZoneException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use LogicException;
use Throwable;

final class ScheduledReportStoreController extends Controller
{
    /**
     * @throws DateInvalidTimeZoneException
     * @throws Throwable
     */
    public function __invoke(
        StoreScheduledReportRequest $request,
        SavedReport $savedReport,
        NextReportRunCalculator $calculator,
        WebAuditRecorder $audit,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $employee = $user->employee;

        if (! $employee instanceof Employee) {
            throw new LogicException(
                'The authenticated user has no employee profile.',
            );
        }

        $format = $request->exportFormat();
        $frequency = $request->frequency();

        $nextRunAt = $calculator->calculate(
            frequency: $frequency,
            runTime: (string) $request->validated('run_time'),
            timezone: $request->timezone(),
            after: CarbonImmutable::now('UTC'),
            weekday: $request->weekday(),
            dayOfMonth: $request->dayOfMonth(),
        );

        DB::transaction(
            function () use (
                $request,
                $savedReport,
                $employee,
                $nextRunAt,
                $format,
                $frequency,
                $audit,
            ) {
                /** @var ScheduledReport $schedule */
                $schedule = $employee
                    ->createdScheduledReports()
                    ->create([
                        'saved_report_id' => $savedReport->getKey(),
                        'name' => (string) $request->validated('name'),
                        'format' => $format,
                        'frequency' => $frequency,
                        'run_time' => $request->validated('run_time'),
                        'timezone' => $request->timezone()->getName(),
                        'weekday' => $request->weekday(),
                        'day_of_month' => $request->dayOfMonth(),
                        'status' => ScheduledReportStatus::ACTIVE,
                        'next_run_at' => $nextRunAt,
                    ]);

                $audit->record(
                    request: $request,
                    action: AuditAction::SCHEDULE_CREATED,
                    outcome: AuditOutcome::SUCCEEDED,
                    actor: $employee,
                    dataset: $savedReport->dataset,
                    subjectType: AuditSubjectType::SCHEDULED_REPORT,
                    subjectId: $schedule->getKey(),
                    context: AuditContext::from([
                        'definition_version' => $savedReport
                            ->definition_version,
                        'format' => $format->value,
                        'frequency' => $frequency->value,
                        'status_to' => ScheduledReportStatus::ACTIVE->value,
                    ]),
                );
            },
        );

        return to_route('analytics.scheduled-reports.index')
            ->with(
                'status',
                'Scheduled report created successfully.',
            );
    }
}
