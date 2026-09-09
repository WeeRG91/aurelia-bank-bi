<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Analytics\Queries\AuthorizedDatasetQueryExecutor;
use App\Analytics\Queries\Sources\DatasetSourceRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\ReportPreviewRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use LogicException;
use Throwable;

final class ReportPreviewController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(
        ReportPreviewRequest $request,
        DatasetSourceRegistry $sources,
        AuthorizedDatasetQueryExecutor $executor,
        WebAuditRecorder $audit,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $employee = $user->employee;

        if (! $employee instanceof Employee) {
            throw new LogicException(
                'The authenticated user has no employee profile.',
            );
        }

        $query = $request->toDatasetQuery();
        $startedAt = microtime(true);

        $rows = $executor->executeFor(
            $user,
            $sources->get($query->dataset),
            $query,
        );

        $audit->record(
            request: $request,
            action: AuditAction::REPORT_PREVIEWED,
            outcome: AuditOutcome::SUCCEEDED,
            actor: $employee,
            dataset: $query->dataset,
            subjectType: AuditSubjectType::DATASET,
            subjectId: $query->dataset->value,
            context: AuditContext::from([
                'dimension_count' => count($query->dimensions),
                'duration_ms' => max(
                    0,
                    (int) round(
                        (microtime(true) - $startedAt) * 1_000,
                    ),
                ),
                'filter_count' => count($query->filters),
                'limit' => $query->limit,
                'measure_count' => count($query->measures),
                'row_count' => count($rows),
            ]),
        );

        return response()->json([
            'data' => array_map(
                static fn (object $row): array => get_object_vars($row),
                $rows,
            ),
            'meta' => [
                'dataset' => $query->dataset->value,
                'dimensions' => $query->dimensions,
                'measures' => $query->measures,
                'rowCount' => count($rows),
                'limit' => $query->limit,
                'reportingTimezone' => $query->reportingTimezone->name,
            ],
        ]);
    }
}
