<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Scheduling\NextReportRunCalculator;
use App\Analytics\Scheduling\ScheduledReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\StoreScheduledReportRequest;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use LogicException;

final class ScheduledReportStoreController extends Controller
{
    public function __invoke(
        StoreScheduledReportRequest $request,
        SavedReport $savedReport,
        NextReportRunCalculator $calculator,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $employee = $user->employee;

        if (! $employee instanceof Employee) {
            throw new LogicException(
                'The authenticated user has no employee profile.',
            );
        }

        $nextRunAt = $calculator->calculate(
            frequency: $request->frequency(),
            runTime: (string) $request->validated('run_time'),
            timezone: $request->timezone(),
            after: CarbonImmutable::now('UTC'),
            weekday: $request->weekday(),
            dayOfMonth: $request->dayOfMonth(),
        );

        $employee->createdScheduledReports()->create([
            'saved_report_id' => $savedReport->getKey(),
            'name' => (string) $request->validated('name'),
            'format' => $request->exportFormat(),
            'frequency' => $request->frequency(),
            'run_time' => $request->validated('run_time'),
            'timezone' => $request->timezone()->getName(),
            'weekday' => $request->weekday(),
            'day_of_month' => $request->dayOfMonth(),
            'status' => ScheduledReportStatus::ACTIVE,
            'next_run_at' => $nextRunAt,
        ]);

        return to_route('analytics.scheduled-reports.index')
            ->with(
                'status',
                'Scheduled report created successfully.',
            );
    }
}
