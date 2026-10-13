<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class DashboardWidgetDestroyController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(
        Request $request,
        Dashboard $dashboard,
        DashboardWidget $dashboardWidget,
        WebAuditRecorder $audit,
    ): Response {
        abort_unless(
            $dashboardWidget->dashboard_id
            === $dashboard->getKey(),
            404,
        );

        Gate::forUser($request->user())
            ->authorize('delete', $dashboardWidget);

        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        $widgetId = (int) $dashboardWidget->getKey();
        $widgetType = $dashboardWidget
            ->widget_type
            ->value;

        DB::transaction(
            function () use (
                $request,
                $dashboard,
                $dashboardWidget,
                $audit,
                $employee,
                $widgetId,
                $widgetType,
            ): void {
                $dashboardWidget->delete();
                $dashboard->touch();

                $audit->record(
                    request: $request,
                    action: AuditAction::DASHBOARD_WIDGET_DELETED,
                    outcome: AuditOutcome::SUCCEEDED,
                    actor: $employee,
                    subjectType: AuditSubjectType::DASHBOARD_WIDGET,
                    subjectId: $widgetId,
                    context: AuditContext::from([
                        'widget_type' => $widgetType,
                    ]),
                );
            },
        );

        return response()->noContent();
    }
}
