<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\SavedReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class SavedReportDestroyController extends Controller
{
    public function __invoke(
        SavedReport $savedReport,
    ): RedirectResponse {
        Gate::authorize('delete', $savedReport);

        $savedReport->delete();

        return to_route('analytics.saved-reports.index')
            ->with('status', 'Report moved to the recycle bin.');
    }
}
