<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class SavedReportRestoreController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(
        Request $request,
        int $savedReport,
        WebAuditRecorder $audit,
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

        DB::transaction(
            function () use (
                $request,
                $report,
                $audit,
                $employee,
            ): void {
                $report->restore();

                $audit->record(
                    request: $request,
                    action: AuditAction::REPORT_RESTORED,
                    outcome: AuditOutcome::SUCCEEDED,
                    actor: $employee,
                    dataset: $report->dataset,
                    subjectType: AuditSubjectType::SAVED_REPORT,
                    subjectId: $report->getKey(),
                    context: AuditContext::from([
                        'definition_version' => $report
                            ->definition_version,
                        'status_from' => 'deleted',
                        'status_to' => 'active',
                    ]),
                );
            },
        );

        return to_route('analytics.saved-reports.index')
            ->with('status', 'Report restored successfully.');
    }
}
