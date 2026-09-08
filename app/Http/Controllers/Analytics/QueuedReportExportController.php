<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Exports\ReportExportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\ExportSavedReportRequest;
use App\Jobs\GenerateReportExport;
use App\Models\Employee;
use App\Models\ReportExport;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use LogicException;
use Throwable;

final class QueuedReportExportController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(
        ExportSavedReportRequest $request,
        SavedReport $savedReport,
    ): JsonResponse|RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $employee = $user->employee;
        $dataset = $savedReport->dataset;
        $definition = $savedReport->definition;

        if (! $employee instanceof Employee) {
            throw new LogicException(
                'The authenticated user has no employee profile.',
            );
        }

        if (
            ! $dataset instanceof DatasetKey
            || ! is_array($definition)
        ) {
            throw new LogicException(
                'The saved report definition is invalid.',
            );
        }

        $export = DB::transaction(
            function () use (
                $employee,
                $savedReport,
                $dataset,
                $definition,
                $request,
            ): ReportExport {
                $export = $employee
                    ->requestedReportExports()
                    ->create([
                        'saved_report_id' => $savedReport->getKey(),
                        'dataset' => $dataset,
                        'definition_version' => $savedReport->definition_version,
                        'definition' => $definition,
                        'format' => $request->exportFormat(),
                        'status' => ReportExportStatus::QUEUED,
                    ]);

                GenerateReportExport::dispatch(
                    (string) $export->getKey(),
                )->afterCommit();

                return $export;
            },
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your export has been queued.',
                'data' => [
                    'id' => $export->getKey(),
                    'status' => $export->status->value,
                    'format' => $export->format->value,
                ],
            ], 202);
        }

        return to_route('analytics.saved-reports.index')
            ->with(
                'status',
                sprintf(
                    '%s export queued successfully.',
                    strtoupper($export->format->value),
                ),
            );
    }
}
