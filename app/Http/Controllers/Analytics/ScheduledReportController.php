<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Exports\ExportFormat;
use App\Analytics\Scheduling\ReportScheduleFrequency;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\ScheduledReport;
use App\Models\User;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class ScheduledReportController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ScheduledReport::class);

        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        $schedules = $employee
            ->createdScheduledReports()
            ->with(
                'savedReport:id,owner_employee_id,name,dataset',
            )
            ->latest()
            ->paginate(20);

        return view('analytics.scheduled-reports.index', [
            'schedules' => $schedules,
        ]);
    }

    public function create(SavedReport $savedReport): View
    {
        Gate::authorize('schedule', $savedReport);

        return view('analytics.scheduled-reports.create', [
            'savedReport' => $savedReport,
            'formats' => ExportFormat::cases(),
            'frequencies' => ReportScheduleFrequency::cases(),
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }
}
