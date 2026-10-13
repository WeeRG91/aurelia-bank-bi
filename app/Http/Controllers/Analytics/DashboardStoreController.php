<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Analytics\Dashboards\DashboardManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\StoreDashboardRequest;
use App\Http\Resources\Analytics\DashboardResource;
use App\Models\Dashboard;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DashboardStoreController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(
        StoreDashboardRequest $request,
        DashboardManager $manager,
        WebAuditRecorder $audit,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        $dashboard = DB::transaction(
            function () use (
                $request,
                $manager,
                $audit,
                $employee,
            ): Dashboard {
                $dashboard = $manager->create(
                    $employee,
                    $request->toDashboardAttributes(),
                );

                $audit->record(
                    request: $request,
                    action: AuditAction::DASHBOARD_CREATED,
                    outcome: AuditOutcome::SUCCEEDED,
                    actor: $employee,
                    subjectType: AuditSubjectType::DASHBOARD,
                    subjectId: $dashboard->getKey(),
                    context: AuditContext::from([
                        'widget_count' => 0,
                    ]),
                );

                return $dashboard;
            },
        );

        $dashboard->loadCount('widgets');

        return (new DashboardResource($dashboard))
            ->response()
            ->setStatusCode(201);
    }
}
