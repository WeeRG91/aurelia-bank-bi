<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Analytics\Dashboards\DashboardManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\UpdateDashboardRequest;
use App\Http\Resources\Analytics\DashboardResource;
use App\Models\Dashboard;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DashboardUpdateController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(
        UpdateDashboardRequest $request,
        Dashboard $dashboard,
        DashboardManager $manager,
        WebAuditRecorder $audit,
    ): DashboardResource {
        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        $dashboard = DB::transaction(
            function () use (
                $request,
                $dashboard,
                $manager,
                $audit,
                $employee,
            ): Dashboard {
                $dashboard = $manager->update(
                    $dashboard,
                    $request->toDashboardAttributes(),
                );

                $audit->record(
                    request: $request,
                    action: AuditAction::DASHBOARD_UPDATED,
                    outcome: AuditOutcome::SUCCEEDED,
                    actor: $employee,
                    subjectType: AuditSubjectType::DASHBOARD,
                    subjectId: $dashboard->getKey(),
                    context: AuditContext::from([
                        'widget_count' => $dashboard
                            ->widgets()
                            ->count(),
                    ]),
                );

                return $dashboard;
            },
        );

        $dashboard->loadCount('widgets');

        return new DashboardResource($dashboard);
    }
}
