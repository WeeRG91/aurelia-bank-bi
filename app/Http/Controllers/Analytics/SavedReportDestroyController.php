<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class SavedReportDestroyController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(
        Request $request,
        SavedReport $savedReport,
        WebAuditRecorder $audit,
    ): RedirectResponse {
        Gate::authorize('delete', $savedReport);

        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        DB::transaction(
            function () use (
                $request,
                $savedReport,
                $audit,
                $employee,
            ): void {
                $savedReport->delete();

                $audit->record(
                    request: $request,
                    action: AuditAction::REPORT_DELETED,
                    outcome: AuditOutcome::SUCCEEDED,
                    actor: $employee,
                    dataset: $savedReport->dataset,
                    subjectType: AuditSubjectType::SAVED_REPORT,
                    subjectId: $savedReport->getKey(),
                    context: AuditContext::from([
                        'definition_version' => $savedReport
                            ->definition_version,
                        'status_from' => 'active',
                        'status_to' => 'deleted',
                    ]),
                );
            },
        );

        return to_route('analytics.saved-reports.index')
            ->with('status', 'Report moved to the recycle bin.');
    }
}
