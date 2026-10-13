<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\EmployeeDashboardWidgetExecutor;
use App\Analytics\Time\ReportingTimezone;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\DashboardWidget;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Tests\TestCase;

final class EmployeeDashboardWidgetExecutorTest extends TestCase
{
    public function test_authorized_widget_executes_governed_query(): void
    {
        $user = $this->user();
        $report = $this->report();
        $widget = $this->widget($report);

        $gate = $this->createMock(Gate::class);

        $gate->expects($this->once())
            ->method('forUser')
            ->with($user)
            ->willReturn($gate);

        $gate->expects($this->once())
            ->method('allows')
            ->with('export', $report)
            ->willReturn(true);

        $database = $this->createMock(
            DatabaseManager::class,
        );

        $connection = $this->createMock(
            ConnectionInterface::class,
        );

        $database->expects($this->once())
            ->method('connection')
            ->with('warehouse')
            ->willReturn($connection);

        $connection->expects($this->once())
            ->method('statement')
            ->with('SET TRANSACTION READ ONLY')
            ->willReturn(true);

        $connection->expects($this->once())
            ->method('select')
            ->with(
                $this->stringContains(
                    'FROM fact_transactions',
                ),
                $this->callback(
                    static fn (mixed $bindings): bool => is_array(
                        $bindings,
                    ),
                ),
            )
            ->willReturn([
                (object) [
                    'currency' => 'EUR',
                    'total_amount' => '1500.00',
                ],
            ]);

        $connection->expects($this->once())
            ->method('transaction')
            ->with(
                $this->isInstanceOf(Closure::class),
                1,
            )
            ->willReturnCallback(
                static function (
                    Closure $callback,
                    int $attempts,
                ) use ($connection): array {
                    return $callback($connection);
                },
            );

        $this->app->instance(Gate::class, $gate);

        $this->app->instance(
            DatabaseManager::class,
            $database,
        );

        $result = app(
            EmployeeDashboardWidgetExecutor::class,
        )->execute(
            user: $user,
            widget: $widget,
            now: new DateTimeImmutable(
                '2026-10-13T10:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $this->assertSame(
            [
                'widgetId' => 42,
                'type' => 'kpi',
                'status' => 'available',
                'dataset' => 'transactions',
                'dimensions' => ['currency'],
                'measures' => ['total_amount'],
                'visualization' => null,
                'rowCount' => 0,
                'rows' => [],
                'valueCount' => 1,
                'values' => [
                    [
                        'context' => [
                            'currency' => 'EUR',
                        ],
                        'value' => '1500.00',
                    ],
                ],
                'comparisonCount' => 0,
                'comparisons' => [],
            ],
            $result->toArray(),
        );
    }

    public function test_comparison_enabled_kpi_executes_both_periods(): void
    {
        $user = $this->user();
        $report = $this->report();

        $definition = $report->definition;

        $definition['relative_date'] = [
            'dimension' => 'booked_at',
            'preset' => 'last_30_days',
        ];

        $report->definition = $definition;

        $widget = $this->widget($report);

        $widget->configuration = [
            'show_comparison' => true,
        ];

        $gate = $this->createMock(Gate::class);

        $gate->expects($this->once())
            ->method('forUser')
            ->with($user)
            ->willReturn($gate);

        $gate->expects($this->once())
            ->method('allows')
            ->with('export', $report)
            ->willReturn(true);

        $database = $this->createMock(
            DatabaseManager::class,
        );

        $connection = $this->createMock(
            ConnectionInterface::class,
        );

        $database->expects($this->exactly(2))
            ->method('connection')
            ->with('warehouse')
            ->willReturn($connection);

        $connection->expects($this->exactly(2))
            ->method('statement')
            ->with('SET TRANSACTION READ ONLY')
            ->willReturn(true);

        $connection->expects($this->exactly(2))
            ->method('select')
            ->with(
                $this->stringContains(
                    'FROM fact_transactions',
                ),
                $this->callback(
                    static fn (mixed $bindings): bool => is_array(
                        $bindings,
                    ),
                ),
            )
            ->willReturnOnConsecutiveCalls(
                [
                    (object) [
                        'currency' => 'EUR',
                        'total_amount' => '125.00',
                    ],
                ],
                [
                    (object) [
                        'currency' => 'EUR',
                        'total_amount' => '100.00',
                    ],
                ],
            );

        $connection->expects($this->exactly(2))
            ->method('transaction')
            ->with(
                $this->isInstanceOf(Closure::class),
                1,
            )
            ->willReturnCallback(
                static function (
                    Closure $callback,
                    int $attempts,
                ) use ($connection): array {
                    return $callback($connection);
                },
            );

        $this->app->instance(Gate::class, $gate);

        $this->app->instance(
            DatabaseManager::class,
            $database,
        );

        $result = app(
            EmployeeDashboardWidgetExecutor::class,
        )->execute(
            user: $user,
            widget: $widget,
            now: new DateTimeImmutable(
                '2026-10-13T10:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $payload = $result->toArray();

        $this->assertSame(1, $payload['valueCount']);

        $this->assertSame(
            [
                [
                    'context' => [
                        'currency' => 'EUR',
                    ],
                    'value' => '125.00',
                ],
            ],
            $payload['values'],
        );

        $this->assertSame(
            1,
            $payload['comparisonCount'],
        );

        $this->assertSame(
            [
                [
                    'context' => [
                        'currency' => 'EUR',
                    ],
                    'current' => '125.00',
                    'previous' => '100.00',
                    'state' => 'comparable',
                    'percentage_change' => '25.00',
                ],
            ],
            $payload['comparisons'],
        );
    }

    public function test_denied_report_produces_unavailable_result(): void
    {
        $user = $this->user();
        $report = $this->report();
        $widget = $this->widget($report);

        $gate = $this->createMock(Gate::class);

        $gate->expects($this->once())
            ->method('forUser')
            ->with($user)
            ->willReturn($gate);

        $gate->expects($this->once())
            ->method('allows')
            ->with('export', $report)
            ->willReturn(false);

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

        $result = app(
            EmployeeDashboardWidgetExecutor::class,
        )->execute(
            user: $user,
            widget: $widget,
            now: new DateTimeImmutable(
                '2026-10-13T10:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $this->assertSame(
            'unavailable',
            $result->status->value,
        );

        $this->assertSame([], $result->rows);
        $this->assertSame([], $result->values);
        $this->assertNull($result->dataset);
    }

    public function test_missing_report_executes_no_query(): void
    {
        $widget = new DashboardWidget([
            'widget_type' => 'table',
            'configuration' => [
                'row_limit' => 25,
            ],
        ]);

        $widget->setAttribute('id', 84);
        $widget->setRelation('savedReport', null);

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

        $result = app(
            EmployeeDashboardWidgetExecutor::class,
        )->execute(
            user: $this->user(),
            widget: $widget,
            now: new DateTimeImmutable(
                '2026-10-13T10:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $this->assertSame(
            'unavailable',
            $result->status->value,
        );

        $this->assertSame([], $result->rows);
        $this->assertSame([], $result->values);
    }

    private function user(): User
    {
        $employee = (new Employee)->forceFill([
            'id' => 10,
            'branch_id' => 42,
            'role' => EmployeeRole::BRANCH_ANALYST,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $user = new User;
        $user->setRelation('employee', $employee);

        return $user;
    }

    private function report(): SavedReport
    {
        return new SavedReport([
            'owner_employee_id' => 10,
            'name' => 'Transaction total',
            'dataset' => 'transactions',
            'definition_version' => 1,
            'definition' => [
                'dimensions' => ['currency'],
                'measures' => ['total_amount'],
                'filters' => [],
                'relative_date' => null,
                'limit' => 100,
                'visualization' => null,
            ],
        ]);
    }

    private function widget(
        SavedReport $report,
    ): DashboardWidget {
        $widget = new DashboardWidget([
            'saved_report_id' => 30,
            'title' => 'Transaction total',
            'widget_type' => 'kpi',
            'grid_column' => 0,
            'grid_row' => 0,
            'grid_width' => 4,
            'grid_height' => 3,
            'configuration' => [
                'show_comparison' => false,
            ],
        ]);

        $widget->setAttribute('id', 42);
        $widget->setRelation('savedReport', $report);

        return $widget;
    }
}
