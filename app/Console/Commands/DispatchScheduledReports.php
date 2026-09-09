<?php

namespace App\Console\Commands;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditRecorder;
use App\Analytics\Auditing\AuditSource;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetFieldAccess;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Exports\ReportExportStatus;
use App\Analytics\Scheduling\NextReportRunCalculator;
use App\Analytics\Scheduling\ScheduledReportStatus;
use App\Jobs\GenerateReportExport;
use App\Models\Employee;
use App\Models\ReportExport;
use App\Models\SavedReport;
use App\Models\ScheduledReport;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('reports:dispatch-scheduled {--limit=100 : Maximum schedules to dispatch}')]
#[Description('Dispatch due scheduled report exports')]
class DispatchScheduledReports extends Command
{
    /**
     * @throws Throwable
     */
    public function handle(
        NextReportRunCalculator $calculator,
        DatasetAccess $datasetAccess,
        DatasetFieldAccess $fieldAccess,
        AuditRecorder $audit,
    ): int {
        $limit = max(
            1,
            min(1_000, (int) $this->option('limit')),
        );

        $now = CarbonImmutable::now('UTC');

        $scheduleIds = ScheduledReport::query()
            ->where(
                'status',
                ScheduledReportStatus::ACTIVE->value,
            )
            ->where('next_run_at', '<=', $now)
            ->orderBy('next_run_at')
            ->limit($limit)
            ->pluck('id');

        $dispatchedCount = 0;

        foreach ($scheduleIds as $scheduleId) {
            $dispatched = $this->dispatchSchedule(
                (int) $scheduleId,
                $now,
                $calculator,
                $datasetAccess,
                $fieldAccess,
                $audit,
            );

            if ($dispatched) {
                $dispatchedCount++;
            }
        }

        $this->info(sprintf(
            'Dispatched %d scheduled report(s).',
            $dispatchedCount,
        ));

        return self::SUCCESS;
    }

    /**
     * @throws Throwable
     */
    private function dispatchSchedule(
        int $scheduleId,
        CarbonImmutable $now,
        NextReportRunCalculator $calculator,
        DatasetAccess $datasetAccess,
        DatasetFieldAccess $fieldAccess,
        AuditRecorder $audit,
    ): bool {
        return DB::transaction(
            function () use (
                $scheduleId,
                $now,
                $calculator,
                $datasetAccess,
                $fieldAccess,
                $audit,
            ): bool {
                $schedule = ScheduledReport::query()
                    ->whereKey($scheduleId)
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $schedule instanceof ScheduledReport
                    || $schedule->status !== ScheduledReportStatus::ACTIVE
                    || $schedule->next_run_at->isAfter($now)
                ) {
                    return false;
                }

                $schedule->load([
                    'savedReport',
                    'creator.user',
                ]);

                $savedReport = $schedule->savedReport;
                $creator = $schedule->creator;
                $user = $creator?->user;

                if (
                    ! $savedReport instanceof SavedReport
                    || ! $creator instanceof Employee
                    || ! $user instanceof User
                    || $savedReport->owner_employee_id !== $creator->getKey()
                ) {
                    return $this->pauseInvalidSchedule(
                        schedule: $schedule,
                        audit: $audit,
                        outcome: AuditOutcome::FAILED,
                        reasonCode: 'report_owner_unavailable',
                        warning: 'Its report owner or user account is unavailable.',
                        actor: $creator instanceof Employee
                            ? $creator
                            : null,
                        dataset: $savedReport instanceof SavedReport
                            ? $savedReport->dataset
                            : null,
                    );
                }

                $definition = $savedReport->definition;

                if (
                    ! is_array($definition)
                    || ! $datasetAccess->canUse(
                        $user,
                        $savedReport->dataset,
                    )
                    || ! $fieldAccess->canUseDefinition(
                        $user,
                        $savedReport->dataset,
                        $definition,
                    )
                ) {
                    return $this->pauseInvalidSchedule(
                        schedule: $schedule,
                        audit: $audit,
                        outcome: AuditOutcome::DENIED,
                        reasonCode: 'report_access_revoked',
                        warning: 'Its dataset or one or more report fields are no longer available.',
                        actor: $creator,
                        dataset: $savedReport->dataset,
                    );
                }

                try {
                    $nextRunAt = $calculator->calculate(
                        frequency: $schedule->frequency,
                        runTime: $schedule->run_time,
                        timezone: new DateTimeZone(
                            $schedule->timezone,
                        ),
                        after: $now,
                        weekday: $schedule->weekday,
                        dayOfMonth: $schedule->day_of_month,
                    );
                } catch (Throwable $exception) {
                    return $this->pauseInvalidSchedule(
                        schedule: $schedule,
                        audit: $audit,
                        outcome: AuditOutcome::FAILED,
                        reasonCode: 'schedule_configuration_invalid',
                        warning: $exception->getMessage(),
                        actor: $creator,
                        dataset: $savedReport->dataset,
                    );
                }

                /** @var ReportExport $export */
                $export = $schedule->exports()->create([
                    'saved_report_id' => $savedReport->getKey(),
                    'requested_by_employee_id' => $creator->getKey(),
                    'dataset' => $savedReport->dataset,
                    'definition_version' => $savedReport->definition_version,
                    'definition' => $savedReport->definition,
                    'format' => $schedule->format,
                    'status' => ReportExportStatus::QUEUED,
                ]);

                $schedule->forceFill([
                    'last_dispatched_at' => $now,
                    'next_run_at' => $nextRunAt,
                ])->save();

                $audit->record(
                    action: AuditAction::SCHEDULE_DISPATCHED,
                    outcome: AuditOutcome::SUCCEEDED,
                    source: AuditSource::SCHEDULER,
                    actor: $creator,
                    dataset: $savedReport->dataset,
                    subjectType: AuditSubjectType::SCHEDULED_REPORT,
                    subjectId: $schedule->getKey(),
                    context: AuditContext::from([
                        'definition_version' => $savedReport
                            ->definition_version,
                        'format' => $schedule->format->value,
                        'frequency' => $schedule->frequency->value,
                    ]),
                );

                $audit->record(
                    action: AuditAction::EXPORT_QUEUED,
                    outcome: AuditOutcome::SUCCEEDED,
                    source: AuditSource::SCHEDULER,
                    actor: $creator,
                    dataset: $savedReport->dataset,
                    subjectType: AuditSubjectType::REPORT_EXPORT,
                    subjectId: $export->getKey(),
                    context: AuditContext::from([
                        'definition_version' => $savedReport
                            ->definition_version,
                        'format' => $schedule->format->value,
                        'status_to' => ReportExportStatus::QUEUED->value,
                    ]),
                );

                GenerateReportExport::dispatch(
                    (string) $export->getKey(),
                )->afterCommit();

                return true;
            }
        );
    }

    private function pauseInvalidSchedule(
        ScheduledReport $schedule,
        AuditRecorder $audit,
        AuditOutcome $outcome,
        string $reasonCode,
        string $warning,
        ?Employee $actor = null,
        ?DatasetKey $dataset = null,
    ): bool {
        $schedule->forceFill([
            'status' => ScheduledReportStatus::PAUSED,
        ])->save();

        $audit->record(
            action: AuditAction::SCHEDULE_AUTO_PAUSED,
            outcome: $outcome,
            source: $actor instanceof Employee
                ? AuditSource::SCHEDULER
                : AuditSource::SYSTEM,
            actor: $actor,
            dataset: $dataset,
            subjectType: AuditSubjectType::SCHEDULED_REPORT,
            subjectId: $schedule->getKey(),
            context: AuditContext::from([
                'format' => $schedule->format->value,
                'frequency' => $schedule->frequency->value,
                'reason_code' => $reasonCode,
                'status_from' => ScheduledReportStatus::ACTIVE->value,
                'status_to' => ScheduledReportStatus::PAUSED->value,
            ]),
        );

        $this->warn(sprintf(
            'Paused schedule %s: %s',
            $schedule->getKey(),
            $warning,
        ));

        return false;
    }
}
