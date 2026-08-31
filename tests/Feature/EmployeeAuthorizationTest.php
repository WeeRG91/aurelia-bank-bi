<?php

namespace Tests\Feature;

use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class EmployeeAuthorizationTest extends TestCase
{
    public function test_active_employee_can_view_own_profile(): void
    {
        foreach (EmployeeRole::cases() as $role) {
            $user = $this->user(
                role: $role,
                userId: 10,
                branchId: 100,
            );

            $this->assertTrue(
                Gate::forUser($user)->allows(
                    'view',
                    $user->employee,
                ),
                "Expected {$role->value} to view their own profile.",
            );
        }
    }

    public function test_branch_manager_can_view_only_same_branch_staff(): void
    {
        $manager = $this->user(
            EmployeeRole::BRANCH_MANAGER,
            userId: 10,
            branchId: 100,
        );

        $sameBranchEmployee = $this->employee(
            userId: 20,
            branchId: 100,
        );

        $otherBranchEmployee = $this->employee(
            userId: 30,
            branchId: 200,
        );

        $this->assertTrue(
            Gate::forUser($manager)->allows(
                'view',
                $sameBranchEmployee,
            ),
        );

        $this->assertFalse(
            Gate::forUser($manager)->allows(
                'view',
                $otherBranchEmployee,
            ),
        );
    }

    public function test_global_oversight_roles_can_view_all_employees(): void
    {
        $target = $this->employee(
            userId: 20,
            branchId: 200,
        );

        foreach ([
            EmployeeRole::COUNTRY_MANAGER,
            EmployeeRole::AUDITOR,
            EmployeeRole::ADMINISTRATOR,
        ] as $role) {
            $user = $this->user($role, userId: 10);

            $this->assertTrue(
                Gate::forUser($user)->allows('view', $target),
                "Expected {$role->value} to view all employees.",
            );
        }
    }

    public function test_operational_roles_cannot_view_other_employees(): void
    {
        $target = $this->employee(
            userId: 20,
            branchId: 100,
        );

        foreach ([
            EmployeeRole::BRANCH_ANALYST,
            EmployeeRole::FINANCE_ANALYST,
            EmployeeRole::RISK_ANALYST,
        ] as $role) {
            $user = $this->user(
                role: $role,
                userId: 10,
                branchId: 100,
            );

            $this->assertFalse(
                Gate::forUser($user)->allows('view', $target),
                "Expected {$role->value} to be denied.",
            );
        }
    }

    public function test_only_administrator_can_create_or_update_employees(): void
    {
        $target = $this->employee(userId: 20);

        foreach (EmployeeRole::cases() as $role) {
            $user = $this->user($role, userId: 10);
            $expected = $role === EmployeeRole::ADMINISTRATOR;

            $this->assertSame(
                $expected,
                Gate::forUser($user)->allows(
                    'create',
                    Employee::class,
                ),
            );

            $this->assertSame(
                $expected,
                Gate::forUser($user)->allows(
                    'update',
                    $target,
                ),
            );
        }
    }

    public function test_employee_records_cannot_be_deleted(): void
    {
        $administrator = $this->user(
            EmployeeRole::ADMINISTRATOR,
            userId: 10,
        );

        $this->assertFalse(
            Gate::forUser($administrator)->allows(
                'delete',
                $this->employee(userId: 20),
            ),
        );
    }

    public function test_inactive_administrator_is_denied(): void
    {
        $administrator = $this->user(
            role: EmployeeRole::ADMINISTRATOR,
            userId: 10,
            status: EmployeeStatus::SUSPENDED,
        );

        $this->assertFalse(
            Gate::forUser($administrator)->allows(
                'create',
                Employee::class,
            ),
        );
    }

    private function user(
        EmployeeRole $role,
        int $userId,
        ?int $branchId = null,
        EmployeeStatus $status = EmployeeStatus::ACTIVE,
    ): User {
        $employee = $this->employee(
            userId: $userId,
            branchId: $branchId,
            role: $role,
            status: $status,
        );

        $user = (new User)->forceFill([
            'id' => $userId,
        ]);

        $user->setRelation('employee', $employee);

        return $user;
    }

    private function employee(
        int $userId,
        ?int $branchId = null,
        EmployeeRole $role = EmployeeRole::BRANCH_ANALYST,
        EmployeeStatus $status = EmployeeStatus::ACTIVE,
    ): Employee {
        return (new Employee)->forceFill([
            'user_id' => $userId,
            'branch_id' => $branchId,
            'role' => $role,
            'status' => $status,
        ]);
    }
}
