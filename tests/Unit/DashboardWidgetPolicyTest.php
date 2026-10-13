<?php

namespace Tests\Unit;

use App\Enums\EmployeeStatus;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Employee;
use App\Models\User;
use App\Policies\DashboardWidgetPolicy;
use PHPUnit\Framework\TestCase;

final class DashboardWidgetPolicyTest extends TestCase
{
    public function test_dashboard_owner_can_manage_widget(): void
    {
        $user = $this->user(
            employeeId: 10,
            status: EmployeeStatus::ACTIVE,
        );

        $widget = $this->widget(
            ownerEmployeeId: 10,
        );

        $policy = new DashboardWidgetPolicy;

        $this->assertNull($policy->before($user));
        $this->assertTrue($policy->view($user, $widget));
        $this->assertTrue($policy->update($user, $widget));
        $this->assertTrue($policy->delete($user, $widget));
        $this->assertFalse($policy->create($user));
        $this->assertFalse($policy->restore($user, $widget));
        $this->assertFalse($policy->forceDelete($user, $widget));
    }

    public function test_another_employee_cannot_manage_widget(): void
    {
        $user = $this->user(
            employeeId: 20,
            status: EmployeeStatus::ACTIVE,
        );

        $widget = $this->widget(
            ownerEmployeeId: 10,
        );

        $policy = new DashboardWidgetPolicy;

        $this->assertFalse($policy->view($user, $widget));
        $this->assertFalse($policy->update($user, $widget));
        $this->assertFalse($policy->delete($user, $widget));
    }

    public function test_inactive_employee_is_rejected_by_guard(): void
    {
        $user = $this->user(
            employeeId: 10,
            status: EmployeeStatus::INACTIVE,
        );

        $this->assertFalse(
            (new DashboardWidgetPolicy)->before($user),
        );
    }

    private function user(
        int $employeeId,
        EmployeeStatus $status,
    ): User {
        $employee = new Employee;

        $employee->forceFill([
            'id' => $employeeId,
            'status' => $status,
        ]);

        $user = new User;
        $user->setRelation('employee', $employee);

        return $user;
    }

    private function widget(
        int $ownerEmployeeId,
    ): DashboardWidget {
        $dashboard = new Dashboard;

        $dashboard->forceFill([
            'owner_employee_id' => $ownerEmployeeId,
        ]);

        $widget = new DashboardWidget;
        $widget->setRelation('dashboard', $dashboard);

        return $widget;
    }
}
