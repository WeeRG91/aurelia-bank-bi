<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\StoreDashboardWidgetRequest;
use App\Http\Resources\Analytics\DashboardWidgetResource;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DashboardWidgetStoreController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(
        StoreDashboardWidgetRequest $request,
        Dashboard $dashboard,
        WebAuditRecorder $audit,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        /** @var DashboardWidget $widget */
        $widget = DB::transaction(
            function () use (
                $request,
                $dashboard,
                $audit,
                $employee,
            ): DashboardWidget {
                /** @var DashboardWidget $widget */
                $widget = $dashboard
                    ->widgets()
                    ->create(
                        $request->toWidgetAttributes(),
                    );

                $dashboard->touch();

                $audit->record(
                    request: $request,
                    action: AuditAction::DASHBOARD_WIDGET_CREATED,
                    outcome: AuditOutcome::SUCCEEDED,
                    actor: $employee,
                    subjectType: AuditSubjectType::DASHBOARD_WIDGET,
                    subjectId: $widget->getKey(),
                    context: AuditContext::from([
                        'widget_type' => $widget
                            ->widget_type
                            ->value,
                    ]),
                );

                return $widget;
            },
        );

        return (new DashboardWidgetResource($widget))
            ->response()
            ->setStatusCode(201);
    }
}
