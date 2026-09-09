<?php

namespace App\Jobs;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditRecorder;
use App\Analytics\Auditing\AuditSource;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Exports\ReportExportGenerator;
use App\Analytics\Exports\ReportExportStatus;
use App\Models\Employee;
use App\Models\ReportExport;
use App\Models\ScheduledReport;
use App\Models\User;
use App\Notifications\ScheduledReportExportFailed;
use App\Notifications\ScheduledReportExportReady;
use Carbon\CarbonImmutable;
use DateInvalidTimeZoneException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GenerateReportExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public bool $failOnTimeout = true;

    public function __construct(
        public readonly string $exportId,
    ) {
        $this->onConnection('redis');
        $this->onQueue('exports');
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30];
    }

    /**
     * @throws DateInvalidTimeZoneException
     * @throws Throwable
     */
    public function handle(
        ReportExportGenerator $generator,
        AuditRecorder $audit,
    ): void {
        $claimed = ReportExport::query()
            ->whereKey($this->exportId)
            ->where(
                'status',
                ReportExportStatus::QUEUED->value,
            )
            ->update([
                'status' => ReportExportStatus::PROCESSING->value,
                'started_at' => now(),
                'finished_at' => null,
                'failure_message' => null,
            ]);

        if ($claimed !== 1) {
            return;
        }

        $jobStartedAt = microtime(true);

        $export = ReportExport::query()
            ->with([
                'requestedBy.user',
                'scheduledReport',
            ])
            ->findOrFail($this->exportId);

        try {
            /** @var Employee $employee */
            $employee = $export->requestedBy;
            /** @var User $user */
            $user = $employee?->user;

            if (
                ! $employee instanceof Employee
                || ! $user instanceof User
            ) {
                throw new RuntimeException(
                    'The employee who requested this export has no user account.',
                );
            }

            $audit->record(
                action: AuditAction::EXPORT_STARTED,
                outcome: AuditOutcome::SUCCEEDED,
                source: AuditSource::QUEUE,
                actor: $employee,
                dataset: $export->dataset,
                subjectType: AuditSubjectType::REPORT_EXPORT,
                subjectId: $export->getKey(),
                context: AuditContext::from([
                    'definition_version' => $export
                        ->definition_version,
                    'format' => $export->format->value,
                    'status_from' => ReportExportStatus::QUEUED->value,
                    'status_to' => ReportExportStatus::PROCESSING->value,
                ]),
            );

            $file = $generator->generate(
                user: $user,
                dataset: $export->dataset,
                definition: $export->definition,
                format: $export->format,
            );

            $disk = (string) config(
                'analytics.export_disk',
                'report_exports',
            );

            $path = sprintf(
                '%s/report.%s',
                $export->getKey(),
                $export->format->extension(),
            );

            Storage::disk($disk)->put(
                $path,
                $file->contents,
            );

            $finishedAt = now();
            $retentionDays = max(
                1,
                (int) config(
                    'analytics.export_retention_days',
                    7,
                ),
            );

            $export->forceFill([
                'status' => ReportExportStatus::COMPLETED,
                'disk' => $disk,
                'path' => $path,
                'filename' => sprintf(
                    'aurelia-report-%s.%s',
                    $export->getKey(),
                    $export->format->extension(),
                ),
                'row_count' => $file->rowCount,
                'file_size_bytes' => strlen($file->contents),
                'finished_at' => $finishedAt,
                'expires_at' => $finishedAt
                    ->copy()
                    ->addDays($retentionDays),
                'failure_message' => null,
            ])->save();

            $audit->record(
                action: AuditAction::EXPORT_COMPLETED,
                outcome: AuditOutcome::SUCCEEDED,
                source: AuditSource::QUEUE,
                actor: $employee,
                dataset: $export->dataset,
                subjectType: AuditSubjectType::REPORT_EXPORT,
                subjectId: $export->getKey(),
                context: AuditContext::from([
                    'definition_version' => $export
                        ->definition_version,
                    'duration_ms' => max(
                        0,
                        (int) round(
                            (microtime(true) - $jobStartedAt) * 1_000,
                        ),
                    ),
                    'file_size_bytes' => $export
                        ->file_size_bytes,
                    'format' => $export->format->value,
                    'row_count' => $export->row_count,
                    'status_from' => ReportExportStatus::PROCESSING->value,
                    'status_to' => ReportExportStatus::COMPLETED->value,
                ]),
            );
        } catch (Throwable $exception) {
            $export->forceFill([
                'status' => ReportExportStatus::QUEUED,
                'started_at' => null,
                'finished_at' => null,
                'failure_message' => null,
            ])->save();

            throw $exception;
        }

        $this->queueScheduledNotification($export);
    }

    private function queueScheduledNotification(ReportExport $export): void
    {
        $schedule = $export->scheduledReport;
        $user = $export->requestedBy?->user;
        $expiresAt = $export->expires_at;

        if (
            ! $schedule instanceof ScheduledReport
            || ! $user instanceof User
            || ! $expiresAt instanceof CarbonImmutable
        ) {
            return;
        }

        $user->notify(
            new ScheduledReportExportReady(
                exportId: (string) $export->getKey(),
                reportName: $schedule->name,
                format: $export->format->value,
                rowCount: $export->row_count ?? 0,
                expiresAt: $expiresAt
                    ->setTimezone($schedule->timezone)
                    ->format('Y-m-d H:i T'),
            ),
        );
    }

    public function failed(
        ?Throwable $exception
    ): void {
        $export = ReportExport::query()
            ->with([
                'requestedBy.user',
                'scheduledReport',
            ])
            ->find($this->exportId);

        if (
            $export === null ||
            $export->status->isTerminal()
        ) {
            return;
        }

        $finishedAt = now();

        $statusFrom = $export->status->value;

        $export->forceFill([
            'status' => ReportExportStatus::FAILED,
            'started_at' => $export->started_at ?? $finishedAt,
            'finished_at' => $finishedAt,
            'failure_message' => Str::limit(
                $exception?->getMessage()
                ?? 'The export job failed unexpectedly.',
                2000,
                '',
            ),
        ])->save();

        $employee = $export->requestedBy;

        $audit = app(AuditRecorder::class);

        $audit->record(
            action: AuditAction::EXPORT_FAILED,
            outcome: AuditOutcome::FAILED,
            source: $employee instanceof Employee
                ? AuditSource::QUEUE
                : AuditSource::SYSTEM,
            actor: $employee instanceof Employee
                ? $employee
                : null,
            dataset: $export->dataset,
            subjectType: AuditSubjectType::REPORT_EXPORT,
            subjectId: $export->getKey(),
            context: AuditContext::from([
                'definition_version' => $export
                    ->definition_version,
                'format' => $export->format->value,
                'reason_code' => 'export_generation_failed',
                'status_from' => $statusFrom,
                'status_to' => ReportExportStatus::FAILED->value,
            ]),
        );

        $schedule = $export->scheduledReport;
        $user = $export->requestedBy?->user;

        if (
            $schedule instanceof ScheduledReport
            && $user instanceof User
        ) {
            $user->notify(
                new ScheduledReportExportFailed(
                    exportId: (string) $export->getKey(),
                    reportName: $schedule->name,
                ),
            );
        }
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return [
            'report-export:'.$this->exportId,
            'queue:exports',
        ];
    }
}
