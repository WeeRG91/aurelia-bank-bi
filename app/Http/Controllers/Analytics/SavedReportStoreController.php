<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Datasets\DatasetKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\StoreSavedReportRequest;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final class SavedReportStoreController extends Controller
{
    public function __invoke(
        StoreSavedReportRequest $request
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        $description = $request->validated('description');

        $report = $employee->savedReports()->create([
            'name' => trim(
                (string) $request->validated('name'),
            ),
            'description' => is_string($description)
                ? trim($description)
                : null,
            'dataset' => DatasetKey::from(
                (string) $request->validated('dataset'),
            ),
            'definition_version' => 1,
            'definition' => $request->toStoredDefinition(),
        ]);

        /** @var SavedReport $report */
        return response()->json([
            'data' => [
                'id' => $report->getKey(),
                'name' => $report->name,
                'description' => $report->description,
                'dataset' => $report->dataset->value,
                'definitionVersion' => $report->definition_version,
                'definition' => $report->definition,
                'createdAt' => $report->created_at?->toAtomString(),
                'updatedAt' => $report->updated_at?->toAtomString(),
            ],
        ], 201);
    }
}
