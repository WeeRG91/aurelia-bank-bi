<?php

namespace App\Jobs;

use App\Analytics\Exports\ReportExportGenerator;
use App\Analytics\Exports\ReportExportStatus;
use App\Models\ReportExport;
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

        $export = ReportExport::query()
            ->with('requestedBy.user')
            ->findOrFail($this->exportId);

        try {
            $user = $export->requestedBy?->user;

            if ($user === null) {
                throw new RuntimeException(
                    'The employee who requested this export has no user account.',
                );
            }

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
        } catch (Throwable $exception) {
            $export->forceFill([
                'status' => ReportExportStatus::QUEUED,
                'started_at' => null,
                'finished_at' => null,
                'failure_message' => null,
            ])->save();

            throw $exception;
        }
    }

    public function failed(
        ?Throwable $exception
    ): void {
        $export = ReportExport::query()
            ->find($this->exportId);

        if (
            $export === null ||
            $export->status->isTerminal()
        ) {
            return;
        }

        $finishedAt = now();

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
