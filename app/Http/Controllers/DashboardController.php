<?php

namespace App\Http\Controllers;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Analytics\Dashboards\DashboardDataProvider;
use App\Analytics\Dashboards\DashboardPeriod;
use App\Analytics\Dashboards\DashboardWidgetResult;
use App\Analytics\Time\ReportingTimezone;
use App\Http\Requests\DashboardRequest;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateInvalidTimeZoneException;
use Illuminate\View\View;
use JsonException;
use LogicException;
use Throwable;

final class DashboardController extends Controller
{
    /**
     * @throws DateInvalidTimeZoneException
     * @throws JsonException
     * @throws Throwable
     */
    public function __invoke(
        DashboardRequest $request,
        DashboardDataProvider $dashboard,
        WebAuditRecorder $audit,
    ): View {
        $startedAt = hrtime(true);

        /** @var User $user */
        $user = $request->user();

        $user->loadMissing('employee.branch');

        $employee = $user->employee;

        if (! $employee instanceof Employee) {
            throw new LogicException(
                'The authenticated user has no employee profile.',
            );
        }

        $reportingTimezone = new ReportingTimezone(
            (string) config('analytics.reporting_timezone'),
        );

        $period = $request->period();

        $widgets = $dashboard->forUser(
            user: $user,
            now: CarbonImmutable::now('UTC'),
            reportingTimezone: $reportingTimezone,
            relativeDatePreset: $period->relativeDatePreset(),
        );

        $audit->record(
            request: $request,
            action: AuditAction::DASHBOARD_VIEWED,
            outcome: AuditOutcome::SUCCEEDED,
            actor: $employee,
            context: AuditContext::from([
                'duration_ms' => (int) (
                    (hrtime(true) - $startedAt) / 1_000_000
                ),
                'widget_count' => count($widgets),
                'period' => $period->value,
            ]),
        );

        $bootstrap = [
            'reportingTimezone' => $reportingTimezone->name,
            'selectedPeriod' => $period->value,
            'periods' => array_map(
                static fn (DashboardPeriod $period): array => [
                    'value' => $period->value,
                    'label' => $period->label(),
                ],
                DashboardPeriod::cases(),
            ),
            'widgets' => array_map(
                static fn (
                    DashboardWidgetResult $result,
                ): array => $result->toArray(),
                $widgets,
            ),
            'dashboardUrl' => route('dashboard'),
        ];

        return view('dashboard', [
            'user' => $user,
            'bootstrapJson' => json_encode(
                $bootstrap,
                JSON_THROW_ON_ERROR
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT,
            ),
        ]);
    }
}
