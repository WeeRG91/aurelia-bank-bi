<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class SavedReportRestoreController extends Controller
{
    public function __invoke(
        Request $request,
        int $savedReport,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        $report = $employee
            ->savedReports()
            ->onlyTrashed()
            ->findOrFail($savedReport);

        Gate::authorize('restore', $report);

        $report->restore();

        return to_route('analytics.saved-reports.index')
            ->with('status', 'Report restored successfully.');
    }
}
