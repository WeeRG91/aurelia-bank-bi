<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Analytics\Datasets\DatasetKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\StoreSavedReportRequest;
use App\Http\Resources\Analytics\SavedReportResource;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SavedReportStoreController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(
        StoreSavedReportRequest $request,
        WebAuditRecorder $audit,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        $description = $request->validated('description');
        $dataset = DatasetKey::from(
            (string) $request->validated('dataset'),
        );
        $definition = $request->toStoredDefinition();

        /** @var SavedReport $report */
        $report = DB::transaction(
            function () use (
                $request,
                $audit,
                $employee,
                $description,
                $dataset,
                $definition,
            ): SavedReport {
                /** @var SavedReport $report */
                $report = $employee->savedReports()->create([
                    'name' => trim(
                        (string) $request->validated('name'),
                    ),
                    'description' => is_string($description)
                        ? trim($description)
                        : null,
                    'dataset' => $dataset,
                    'definition_version' => 1,
                    'definition' => $definition,
                ]);

                $audit->record(
                    request: $request,
                    action: AuditAction::REPORT_CREATED,
                    outcome: AuditOutcome::SUCCEEDED,
                    actor: $employee,
                    dataset: $dataset,
                    subjectType: AuditSubjectType::SAVED_REPORT,
                    subjectId: $report->getKey(),
                    context: AuditContext::from([
                        'definition_version' => 1,
                        'dimension_count' => count(
                            $definition['dimensions'],
                        ),
                        'filter_count' => count(
                            $definition['filters'],
                        ),
                        'limit' => $definition['limit'],
                        'measure_count' => count(
                            $definition['measures'],
                        ),
                    ]),
                );

                return $report;
            },
        );

        return (new SavedReportResource($report))
            ->response()
            ->setStatusCode(201);
    }
}
