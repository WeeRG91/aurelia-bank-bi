<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\UpdateDashboardWidgetRequest;
use App\Http\Resources\Analytics\DashboardWidgetResource;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DashboardWidgetUpdateController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(
        UpdateDashboardWidgetRequest $request,
        Dashboard $dashboard,
        DashboardWidget $dashboardWidget,
        WebAuditRecorder $audit,
    ): DashboardWidgetResource {
        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        DB::transaction(
            function () use (
                $request,
                $dashboard,
                $dashboardWidget,
                $audit,
                $employee,
            ): void {
                $dashboardWidget->update(
                    $request->toWidgetAttributes(),
                );

                $dashboard->touch();

                $audit->record(
                    request: $request,
                    action: AuditAction::DASHBOARD_WIDGET_UPDATED,
                    outcome: AuditOutcome::SUCCEEDED,
                    actor: $employee,
                    subjectType: AuditSubjectType::DASHBOARD_WIDGET,
                    subjectId: $dashboardWidget->getKey(),
                    context: AuditContext::from([
                        'widget_type' => $dashboardWidget
                            ->widget_type
                            ->value,
                    ]),
                );
            },
        );

        return new DashboardWidgetResource(
            $dashboardWidget->refresh(),
        );
    }
}
