<?php

namespace Tests\Unit;

use App\Enums\EmployeeStatus;
use App\Models\Dashboard;
use App\Models\Employee;
use App\Models\User;
use App\Policies\DashboardPolicy;
use PHPUnit\Framework\TestCase;

final class DashboardPolicyTest extends TestCase
{
    public function test_owner_can_manage_dashboard(): void
    {
        $user = $this->user(
            employeeId: 10,
            status: EmployeeStatus::ACTIVE,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
        );

        $policy = new DashboardPolicy;

        $this->assertNull($policy->before($user));
        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->view($user, $dashboard));
        $this->assertTrue($policy->update($user, $dashboard));
        $this->assertTrue($policy->delete($user, $dashboard));
        $this->assertTrue($policy->restore($user, $dashboard));
        $this->assertTrue($policy->addWidget($user, $dashboard));
        $this->assertFalse($policy->forceDelete($user, $dashboard));
    }

    public function test_another_employee_cannot_manage_dashboard(): void
    {
        $user = $this->user(
            employeeId: 20,
            status: EmployeeStatus::ACTIVE,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
        );

        $policy = new DashboardPolicy;

        $this->assertFalse($policy->view($user, $dashboard));
        $this->assertFalse($policy->update($user, $dashboard));
        $this->assertFalse($policy->delete($user, $dashboard));
        $this->assertFalse($policy->restore($user, $dashboard));
        $this->assertFalse($policy->addWidget($user, $dashboard));
    }

    public function test_inactive_employee_is_rejected_by_guard(): void
    {
        $user = $this->user(
            employeeId: 10,
            status: EmployeeStatus::INACTIVE,
        );

        $this->assertFalse(
            (new DashboardPolicy)->before($user),
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

    private function dashboard(
        int $ownerEmployeeId,
    ): Dashboard {
        $dashboard = new Dashboard;

        $dashboard->forceFill([
            'owner_employee_id' => $ownerEmployeeId,
        ]);

        return $dashboard;
    }
}
