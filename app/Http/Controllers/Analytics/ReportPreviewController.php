<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Queries\AuthorizedDatasetQueryExecutor;
use App\Analytics\Queries\Sources\DatasetSourceRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\ReportPreviewRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $query = $request->toDatasetQuery();

        $rows = $executor->executeFor(
            $user,
            $sources->get($query->dataset),
            $query,
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
