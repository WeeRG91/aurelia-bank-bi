<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class DashboardDestroyController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(
        Request $request,
        Dashboard $dashboard,
        WebAuditRecorder $audit,
    ): Response {
        Gate::forUser($request->user())
            ->authorize('delete', $dashboard);

        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        $dashboardId = (int) $dashboard->getKey();
        $widgetCount = $dashboard->widgets()->count();

        DB::transaction(
            function () use (
                $request,
                $dashboard,
                $dashboardId,
                $widgetCount,
                $audit,
                $employee,
            ): void {
                $dashboard->delete();

                $audit->record(
                    request: $request,
                    action: AuditAction::DASHBOARD_DELETED,
                    outcome: AuditOutcome::SUCCEEDED,
                    actor: $employee,
                    subjectType: AuditSubjectType::DASHBOARD,
                    subjectId: $dashboardId,
                    context: AuditContext::from([
                        'widget_count' => $widgetCount,
                    ]),
                );
            },
        );

        return response()->noContent();
    }
}
