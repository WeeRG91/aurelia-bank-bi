<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Datasets\DatasetAccess;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class SavedReportDuplicateController extends Controller
{
    public function __invoke(
        Request $request,
        SavedReport $savedReport,
        DatasetAccess $datasetAccess,
    ): RedirectResponse {
        Gate::authorize('view', $savedReport);
        Gate::authorize('create', SavedReport::class);

        /** @var User $user */
        $user = $request->user();

        abort_unless(
            $datasetAccess->canUse(
                $user,
                $savedReport->dataset,
            ),
            403,
        );

        /** @var Employee $employee */
        $employee = $user->employee;

        try {
            $copy = $employee->savedReports()->create([
                'name' => Str::limit(
                    $savedReport->name,
                    143,
                    '',
                ).' (Copy)',
                'description' => $savedReport->description,
                'dataset' => $savedReport->dataset,
                'definition_version' => $savedReport->definition_version,
                'definition' => $savedReport->definition,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return to_route('analytics.saved-reports.index')
                ->with(
                    'error',
                    'The report could not be duplicated. Please try again.',
                );
        }

        return to_route(
            'analytics.report-builder',
            ['savedReport' => $copy],
        )->with(
            'status',
            "“{$copy->name}” was duplicated successfully.",
        );
    }
}
