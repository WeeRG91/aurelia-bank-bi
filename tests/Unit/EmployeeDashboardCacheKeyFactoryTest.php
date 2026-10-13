<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardCacheGeneration;
use App\Analytics\Dashboards\EmployeeDashboardCacheKeyFactory;
use App\Analytics\Time\ReportingTimezone;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

final class EmployeeDashboardCacheKeyFactoryTest extends TestCase
{
    public function test_equivalent_dashboard_state_produces_stable_key(): void
    {
        $generation = new class implements DashboardCacheGeneration
        {
            public function current(): string
            {
                return 'test-generation';
            }

            public function rotate(): string
            {
                return 'rotated-generation';
            }
        };

        $factory = new EmployeeDashboardCacheKeyFactory(
            $generation,
        );

        $employee = (new Employee)->forceFill([
            'id' => 10,
            'branch_id' => 2,
            'role' => EmployeeRole::BRANCH_ANALYST,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $user = (new User)->forceFill([
            'id' => 100,
        ]);

        $user->setRelation('employee', $employee);

        $dashboard = (new Dashboard)->forceFill([
            'id' => 50,
            'owner_employee_id' => 10,
            'name' => 'Treasury overview',
            'is_default' => true,
            'updated_at' => '2026-10-13 10:00:00',
        ]);

        $dashboard->setRelation(
            'widgets',
            new Collection,
        );

        $timezone = new ReportingTimezone(
            'Europe/Luxembourg',
        );

        $now = new DateTimeImmutable(
            '2026-10-13T12:00:00+02:00',
        );

        $first = $factory->forUser(
            $user,
            $dashboard,
            $timezone,
            $now,
        );

        $second = $factory->forUser(
            $user,
            $dashboard,
            $timezone,
            $now,
        );

        $this->assertSame($first, $second);

        $this->assertMatchesRegularExpression(
            '/^analytics:employee-dashboard:v1:[a-f0-9]{64}$/',
            (string) $first,
        );
    }

    public function test_saved_report_definition_changes_the_key(): void
    {
        $generation = new class implements DashboardCacheGeneration
        {
            public function current(): string
            {
                return 'test-generation';
            }

            public function rotate(): string
            {
                return 'rotated-generation';
            }
        };

        $factory = new EmployeeDashboardCacheKeyFactory(
            $generation,
        );

        $employee = (new Employee)->forceFill([
            'id' => 10,
            'branch_id' => 2,
            'role' => EmployeeRole::BRANCH_ANALYST,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $user = (new User)->forceFill([
            'id' => 100,
        ]);

        $user->setRelation('employee', $employee);

        $report = (new SavedReport)->forceFill([
            'id' => 80,
            'owner_employee_id' => 10,
            'name' => 'Transaction activity',
            'dataset' => 'transactions',
            'definition_version' => 1,
            'definition' => [
                'dimensions' => ['currency'],
                'measures' => ['transaction_count'],
                'filters' => [],
                'relative_date' => null,
                'limit' => 100,
                'visualization' => null,
            ],
            'updated_at' => '2026-10-13 10:00:00',
        ]);

        $widget = (new DashboardWidget)->forceFill([
            'id' => 70,
            'dashboard_id' => 50,
            'saved_report_id' => 80,
            'title' => 'Transaction activity',
            'widget_type' => 'table',
            'grid_column' => 0,
            'grid_row' => 0,
            'grid_width' => 6,
            'grid_height' => 4,
            'configuration' => [
                'row_limit' => 25,
            ],
            'updated_at' => '2026-10-13 10:00:00',
        ]);

        $widget->setRelation('savedReport', $report);

        $dashboard = (new Dashboard)->forceFill([
            'id' => 50,
            'owner_employee_id' => 10,
            'name' => 'Treasury overview',
            'is_default' => true,
            'updated_at' => '2026-10-13 10:00:00',
        ]);

        $dashboard->setRelation(
            'widgets',
            new Collection([$widget]),
        );

        $timezone = new ReportingTimezone(
            'Europe/Luxembourg',
        );

        $now = new DateTimeImmutable(
            '2026-10-13T12:00:00+02:00',
        );

        $original = $factory->forUser(
            $user,
            $dashboard,
            $timezone,
            $now,
        );

        $report->setAttribute('definition', [
            'dimensions' => ['currency'],
            'measures' => ['transaction_count'],
            'filters' => [],
            'relative_date' => null,
            'limit' => 50,
            'visualization' => null,
        ]);

        $changed = $factory->forUser(
            $user,
            $dashboard,
            $timezone,
            $now,
        );

        $this->assertNotSame($original, $changed);
    }
}
