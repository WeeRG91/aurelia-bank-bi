<?php

namespace App\Console\Commands;

use App\Analytics\Exports\ReportExportStatus;
use App\Models\ReportExport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Signature('reports:expire-exports')]
#[Description('Expire report exports and delete their private files')]
final class ExpireReportExports extends Command
{
    public function handle(): int
    {
        $now = now();
        $expiredCount = 0;
        $failureCount = 0;

        ReportExport::query()
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->where(function (Builder $query) use ($now): void {
                        $query
                            ->where(
                                'status',
                                ReportExportStatus::COMPLETED->value,
                            )
                            ->whereNotNull('expires_at')
                            ->where('expires_at', '<=', $now);
                    })
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->where(
                                'status',
                                ReportExportStatus::EXPIRED->value,
                            )
                            ->whereNotNull('expires_at');
                    });
            })
            ->orderBy('id')
            ->chunkById(
                100,
                function ($exports) use (
                    $now,
                    &$expiredCount,
                    &$failureCount,
                ): void {
                    foreach ($exports as $export) {
                        if (
                            $export->status === ReportExportStatus::COMPLETED
                        ) {
                            $claimed = ReportExport::query()
                                ->whereKey($export->getKey())
                                ->where(
                                    'status',
                                    ReportExportStatus::COMPLETED->value,
                                )
                                ->where('expires_at', '<=', $now)
                                ->update([
                                    'status' => ReportExportStatus::EXPIRED->value,
                                ]);

                            if ($claimed !== 1) {
                                continue;
                            }
                        }

                        try {
                            if (
                                is_string($export->disk)
                                && is_string($export->path)
                            ) {
                                Storage::disk($export->disk)->delete($export->path);
                            }

                            ReportExport::query()
                                ->whereKey($export->getKey())
                                ->where(
                                    'status',
                                    ReportExportStatus::EXPIRED->value,
                                )
                                ->update([
                                    'disk' => null,
                                    'path' => null,
                                ]);

                            $expiredCount++;
                        } catch (Throwable $exception) {
                            $failureCount++;

                            $this->error(sprintf(
                                'Could not delete export %s: %s',
                                $export->getKey(),
                                $exception->getMessage(),
                            ));
                        }
                    }
                },
                column: 'id',
            );

        $this->info(sprintf(
            'Expired %d report export(s).',
            $expiredCount,
        ));

        return $failureCount === 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
