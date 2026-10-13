<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\EmployeeDashboardExecutor;
use App\Analytics\Time\ReportingTimezone;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

final class EmployeeDashboardExecutorTest extends TestCase
{
    public function test_widget_results_preserve_document_order(): void
    {
        $gate = $this->createMock(Gate::class);

        $gate->expects($this->never())
            ->method('forUser');

        $database = $this->createMock(
            DatabaseManager::class,
        );

        $database->expects($this->never())
            ->method('connection');

        $this->app->instance(Gate::class, $gate);

        $this->app->instance(
            DatabaseManager::class,
            $database,
        );

        $firstWidget = $this->unavailableWidget(
            id: 91,
            row: 0,
            column: 0,
        );

        $secondWidget = $this->unavailableWidget(
            id: 24,
            row: 1,
            column: 0,
        );

        $dashboard = new Dashboard([
            'owner_employee_id' => 10,
            'name' => 'Ordered dashboard',
            'is_default' => false,
        ]);

        $dashboard->setAttribute('id', 50);

        $dashboard->setRelation(
            'widgets',
            new Collection([
                $firstWidget,
                $secondWidget,
            ]),
        );

        $results = app(
            EmployeeDashboardExecutor::class,
        )->execute(
            user: new User,
            dashboard: $dashboard,
            now: new DateTimeImmutable(
                '2026-10-13T10:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $this->assertSame(
            [91, 24],
            array_map(
                static fn ($result): int => $result->widgetId,
                $results,
            ),
        );

        $this->assertSame(
            ['unavailable', 'unavailable'],
            array_map(
                static fn ($result): string => $result
                    ->status
                    ->value,
                $results,
            ),
        );
    }

    public function test_empty_dashboard_returns_no_results(): void
    {
        $dashboard = new Dashboard([
            'owner_employee_id' => 10,
            'name' => 'Empty dashboard',
            'is_default' => false,
        ]);

        $dashboard->setAttribute('id', 50);

        $dashboard->setRelation(
            'widgets',
            new Collection,
        );

        $results = app(
            EmployeeDashboardExecutor::class,
        )->execute(
            user: new User,
            dashboard: $dashboard,
            now: new DateTimeImmutable(
                '2026-10-13T10:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $this->assertSame([], $results);
    }

    private function unavailableWidget(
        int $id,
        int $row,
        int $column,
    ): DashboardWidget {
        $widget = new DashboardWidget([
            'dashboard_id' => 50,
            'saved_report_id' => 500 + $id,
            'title' => "Widget {$id}",
            'widget_type' => 'table',
            'grid_column' => $column,
            'grid_row' => $row,
            'grid_width' => 6,
            'grid_height' => 4,
            'configuration' => [
                'row_limit' => 25,
            ],
        ]);

        $widget->setAttribute('id', $id);
        $widget->setRelation('savedReport', null);

        return $widget;
    }
}
