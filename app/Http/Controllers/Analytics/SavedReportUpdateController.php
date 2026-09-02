<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Datasets\DatasetKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\UpdateSavedReportRequest;
use App\Http\Resources\Analytics\SavedReportResource;
use App\Models\SavedReport;

final class SavedReportUpdateController extends Controller
{
    public function __invoke(
        UpdateSavedReportRequest $request,
        SavedReport $savedReport
    ): SavedReportResource {
        $description = $request->validated('description');

        $savedReport->update([
            'name' => trim(
                (string) $request->validated('name'),
            ),
            'description' => is_string($description)
                ? trim($description)
                : null,
            'dataset' => DatasetKey::from(
                (string) $request->validated('dataset'),
            ),
            'definition' => $request->toStoredDefinition(),
        ]);

        return new SavedReportResource($savedReport);
    }
}
