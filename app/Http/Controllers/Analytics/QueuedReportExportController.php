<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
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
        WebAuditRecorder $audit,
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

        $format = $request->exportFormat();

        $export = DB::transaction(
            function () use (
                $employee,
                $savedReport,
                $dataset,
                $definition,
                $request,
                $audit,
                $format,
            ): ReportExport {
                $export = $employee
                    ->requestedReportExports()
                    ->create([
                        'saved_report_id' => $savedReport->getKey(),
                        'dataset' => $dataset,
                        'definition_version' => $savedReport->definition_version,
                        'definition' => $definition,
                        'format' => $format,
                        'status' => ReportExportStatus::QUEUED,
                    ]);

                $audit->record(
                    request: $request,
                    action: AuditAction::EXPORT_QUEUED,
                    outcome: AuditOutcome::SUCCEEDED,
                    actor: $employee,
                    dataset: $dataset,
                    subjectType: AuditSubjectType::REPORT_EXPORT,
                    subjectId: $export->getKey(),
                    context: AuditContext::from([
                        'definition_version' => $savedReport
                            ->definition_version,
                        'dimension_count' => count(
                            $definition['dimensions'] ?? [],
                        ),
                        'filter_count' => count(
                            $definition['filters'] ?? [],
                        ),
                        'format' => $format->value,
                        'limit' => (int) ($definition['limit'] ?? 100),
                        'measure_count' => count(
                            $definition['measures'] ?? [],
                        ),
                        'status_to' => ReportExportStatus::QUEUED->value,
                    ]),
                );

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
