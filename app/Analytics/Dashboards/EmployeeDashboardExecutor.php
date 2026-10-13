<?php

namespace App\Analytics\Dashboards;

use App\Analytics\Time\ReportingTimezone;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\User;
use DateInvalidTimeZoneException;
use DateTimeImmutable;
use Throwable;

final readonly class EmployeeDashboardExecutor
{
    public function __construct(
        private EmployeeDashboardWidgetExecutor $widgetExecutor,
    ) {}

    /**
     * @return list<EmployeeDashboardWidgetResult>
     *
     * @throws DateInvalidTimeZoneException
     * @throws Throwable
     */
    public function execute(
        User $user,
        Dashboard $dashboard,
        DateTimeImmutable $now,
        ReportingTimezone $reportingTimezone,
    ): array {
        $dashboard->loadMissing('widgets');

        $results = [];

        foreach ($dashboard->widgets as $widget) {
            if (! $widget instanceof DashboardWidget) {
                continue;
            }

            $widget->loadMissing('savedReport');

            $results[] = $this->widgetExecutor->execute(
                user: $user,
                widget: $widget,
                now: $now,
                reportingTimezone: $reportingTimezone,
            );
        }

        return $results;
    }
}
