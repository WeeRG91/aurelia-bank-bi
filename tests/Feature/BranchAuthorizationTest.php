<?php

namespace Tests\Feature;

use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class BranchAuthorizationTest extends TestCase
{
    public function test_branch_roles_can_view_only_their_branch(): void
    {
        $assignedBranch = $this->branch(10);
        $otherBranch = $this->branch(20);

        foreach ([
            EmployeeRole::BRANCH_ANALYST,
            EmployeeRole::BRANCH_MANAGER,
        ] as $role) {
            $user = $this->user($role, 10);

            $this->assertTrue(
                Gate::forUser($user)->allows(
                    'view',
                    $assignedBranch,
                ),
            );

            $this->assertFalse(
                Gate::forUser($user)->allows(
                    'view',
                    $otherBranch,
                ),
            );
        }
    }

    public function test_head_office_roles_can_view_all_branches(): void
    {
        $branch = $this->branch(20);

        foreach ([
            EmployeeRole::COUNTRY_MANAGER,
            EmployeeRole::FINANCE_ANALYST,
            EmployeeRole::RISK_ANALYST,
            EmployeeRole::AUDITOR,
            EmployeeRole::ADMINISTRATOR,
        ] as $role) {
            $user = $this->user($role);

            $this->assertTrue(
                Gate::forUser($user)->allows('view', $branch),
                "Expected {$role->value} to view all branches.",
            );
        }
    }

    public function test_active_employee_can_enter_branch_listing(): void
    {
        $user = $this->user(
            EmployeeRole::BRANCH_ANALYST,
            10,
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'viewAny',
                Branch::class,
            ),
        );
    }

    public function test_inactive_employee_is_denied_before_role_check(): void
    {
        $user = $this->user(
            EmployeeRole::ADMINISTRATOR,
            null,
            EmployeeStatus::SUSPENDED,
        );

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'view',
                $this->branch(10),
            ),
        );
    }

    public function test_user_without_employee_profile_is_denied(): void
    {
        $user = new User;
        $user->setRelation('employee', null);

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'view',
                $this->branch(10),
            ),
        );
    }

    private function branch(int $id): Branch
    {
        return (new Branch)->forceFill([
            'id' => $id,
        ]);
    }

    private function user(
        EmployeeRole $role,
        ?int $branchId = null,
        EmployeeStatus $status = EmployeeStatus::ACTIVE,
    ): User {
        $employee = (new Employee)->forceFill([
            'branch_id' => $branchId,
            'role' => $role,
            'status' => $status,
        ]);

        $user = new User;
        $user->setRelation('employee', $employee);

        return $user;
    }
}
