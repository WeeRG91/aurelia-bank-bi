<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Analytics\Dashboards\EmployeeDashboardCacheKeyFactory;
use App\Analytics\Dashboards\EmployeeDashboardExecutor;
use App\Analytics\Dashboards\EmployeeDashboardResultCache;
use App\Analytics\Dashboards\EmployeeDashboardWidgetResult;
use App\Analytics\Time\ReportingTimezone;
use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateInvalidTimeZoneException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class DashboardResultsController extends Controller
{
    /**
     * @throws DateInvalidTimeZoneException
     * @throws Throwable
     */
    public function __invoke(
        Request $request,
        Dashboard $dashboard,
        EmployeeDashboardExecutor $executor,
        EmployeeDashboardCacheKeyFactory $cacheKeys,
        EmployeeDashboardResultCache $cache,
        WebAuditRecorder $audit,
    ): JsonResponse {
        Gate::forUser($request->user())
            ->authorize('view', $dashboard);

        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        $reportingTimezone = new ReportingTimezone(
            (string) config(
                'analytics.reporting_timezone',
            ),
        );

        $generatedAt = CarbonImmutable::now(
            $reportingTimezone->toDateTimeZone(),
        );

        $startedAt = hrtime(true);

        $cacheKey = $cacheKeys->forUser(
            user: $user,
            dashboard: $dashboard,
            reportingTimezone: $reportingTimezone,
            now: $generatedAt,
        );

        $entry = $cacheKey !== null
            ? $cache->get($cacheKey)
            : null;

        if ($entry !== null) {
            $widgetPayloads = $entry->widgets;
            $responseGeneratedAt = $entry->generatedAt;
            $cacheHit = true;
        } else {
            $results = $executor->execute(
                user: $user,
                dashboard: $dashboard,
                now: $generatedAt,
                reportingTimezone: $reportingTimezone,
            );

            $widgetPayloads = array_map(
                static fn (
                    EmployeeDashboardWidgetResult $result,
                ): array => $result->toArray(),
                $results,
            );

            $responseGeneratedAt = $generatedAt;
            $cacheHit = false;

            if ($cacheKey !== null) {
                $cache->put(
                    key: $cacheKey,
                    widgets: $widgetPayloads,
                    generatedAt: $generatedAt,
                );
            }
        }

        $durationMs = max(
            0,
            (int) floor(
                (hrtime(true) - $startedAt) / 1_000_000,
            ),
        );

        $audit->record(
            request: $request,
            action: AuditAction::DASHBOARD_VIEWED,
            outcome: AuditOutcome::SUCCEEDED,
            actor: $employee,
            subjectType: AuditSubjectType::DASHBOARD,
            subjectId: $dashboard->getKey(),
            context: AuditContext::from([
                'cache_hit' => $cacheHit,
                'duration_ms' => $durationMs,
                'widget_count' => count($widgetPayloads),
            ]),
        );

        return response()->json([
            'data' => $widgetPayloads,
            'meta' => [
                'dashboardId' => $dashboard->getKey(),
                'generatedAt' => $responseGeneratedAt->format(
                    DATE_ATOM,
                ),
                'reportingTimezone' => $reportingTimezone->name,
                'widgetCount' => count($widgetPayloads),
                'cacheHit' => $cacheHit,
            ],
        ]);
    }
}
