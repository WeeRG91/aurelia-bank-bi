<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Analytics\Datasets\DatasetKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\UpdateSavedReportRequest;
use App\Http\Resources\Analytics\SavedReportResource;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SavedReportUpdateController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(
        UpdateSavedReportRequest $request,
        SavedReport $savedReport,
        WebAuditRecorder $audit,
    ): SavedReportResource {
        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        $description = $request->validated('description');
        $dataset = DatasetKey::from(
            (string) $request->validated('dataset'),
        );
        $definition = $request->toStoredDefinition();

        DB::transaction(
            function () use (
                $request,
                $savedReport,
                $audit,
                $employee,
                $description,
                $dataset,
                $definition,
            ): void {
                $savedReport->update([
                    'name' => trim(
                        (string) $request->validated('name'),
                    ),
                    'description' => is_string($description)
                        ? trim($description)
                        : null,
                    'dataset' => $dataset,
                    'definition' => $definition,
                ]);

                $audit->record(
                    request: $request,
                    action: AuditAction::REPORT_UPDATED,
                    outcome: AuditOutcome::SUCCEEDED,
                    actor: $employee,
                    dataset: $dataset,
                    subjectType: AuditSubjectType::SAVED_REPORT,
                    subjectId: $savedReport->getKey(),
                    context: AuditContext::from([
                        'definition_version' => $savedReport
                            ->definition_version,
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
            },
        );

        return new SavedReportResource($savedReport);
    }
}
