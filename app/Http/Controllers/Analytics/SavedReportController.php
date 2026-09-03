<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Datasets\DatasetDefinition;
use App\Analytics\Datasets\DatasetRegistry;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class SavedReportController extends Controller
{
    public function index(
        Request $request,
        DatasetRegistry $datasetRegistry,
    ): View {
        Gate::authorize('viewAny', SavedReport::class);

        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        $reports = $employee
            ->savedReports()
            ->latest('updated_at')
            ->paginate(20);

        $datasetLabels = collect($datasetRegistry->all())
            ->mapWithKeys(
                static fn (DatasetDefinition $dataset): array => [
                    $dataset->key->value => $dataset->label,
                ],
            )
            ->all();

        return view('analytics.saved-reports.index', [
            'reports' => $reports,
            'datasetLabels' => $datasetLabels,
            'showingTrash' => false,
        ]);
    }

    public function trash(
        Request $request,
        DatasetRegistry $datasetRegistry,
    ): View {
        Gate::authorize('viewAny', SavedReport::class);

        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        $reports = $employee
            ->savedReports()
            ->onlyTrashed()
            ->latest('deleted_at')
            ->paginate(20);

        $datasetLabels = collect($datasetRegistry->all())
            ->mapWithKeys(
                static fn (DatasetDefinition $dataset): array => [
                    $dataset->key->value => $dataset->label,
                ],
            )
            ->all();

        return view('analytics.saved-reports.index', [
            'reports' => $reports,
            'datasetLabels' => $datasetLabels,
            'showingTrash' => true,
        ]);
    }
}
