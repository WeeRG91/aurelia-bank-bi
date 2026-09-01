<?php

namespace Tests\Feature;

use App\Authorization\EmployeeAccess;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use Tests\TestCase;

class EmployeeAccessTest extends TestCase
{
    public function test_operational_roles_are_restricted_to_their_own_profile(): void
    {
        foreach ([
            EmployeeRole::BRANCH_ANALYST,
            EmployeeRole::FINANCE_ANALYST,
            EmployeeRole::RISK_ANALYST,
        ] as $role) {
            $query = app(EmployeeAccess::class)
                ->queryFor($this->user($role, userId: 10));

            $this->assertStringContainsString(
                'where "employees"."user_id" = ?',
                strtolower($query->toSql()),
            );

            $this->assertSame([10], $query->getBindings());
        }
    }

    public function test_branch_manager_is_restricted_to_their_branch(): void
    {
        $query = app(EmployeeAccess::class)
            ->queryFor(
                $this->user(
                    EmployeeRole::BRANCH_MANAGER,
                    userId: 10,
                    branchId: 100,
                ),
            );

        $this->assertStringContainsString(
            'where "employees"."branch_id" = ?',
            strtolower($query->toSql()),
        );

        $this->assertSame([100], $query->getBindings());
    }

    public function test_global_roles_can_query_all_employees(): void
    {
        foreach ([
            EmployeeRole::COUNTRY_MANAGER,
            EmployeeRole::AUDITOR,
            EmployeeRole::ADMINISTRATOR,
        ] as $role) {
            $query = app(EmployeeAccess::class)
                ->queryFor($this->user($role, userId: 10));

            $this->assertStringNotContainsString(
                ' where ',
                strtolower($query->toSql()),
            );

            $this->assertSame([], $query->getBindings());
        }
    }

    public function test_directory_access_uses_the_expected_role_matrix(): void
    {
        $access = app(EmployeeAccess::class);

        foreach (EmployeeRole::cases() as $role) {
            $branchId = $role === EmployeeRole::BRANCH_MANAGER
                ? 100
                : null;

            $expected = in_array($role, [
                EmployeeRole::BRANCH_MANAGER,
                EmployeeRole::COUNTRY_MANAGER,
                EmployeeRole::AUDITOR,
                EmployeeRole::ADMINISTRATOR,
            ], true);

            $this->assertSame(
                $expected,
                $access->canViewDirectory(
                    $this->user($role, 10, $branchId),
                ),
                "Unexpected directory access for {$role->value}.",
            );
        }
    }

    public function test_inactive_employee_receives_an_empty_query(): void
    {
        $query = app(EmployeeAccess::class)
            ->queryFor(
                $this->user(
                    EmployeeRole::ADMINISTRATOR,
                    userId: 10,
                    status: EmployeeStatus::SUSPENDED,
                ),
            );

        $this->assertStringContainsString(
            '1 = 0',
            $query->toSql(),
        );
    }

    private function user(
        EmployeeRole $role,
        int $userId,
        ?int $branchId = null,
        EmployeeStatus $status = EmployeeStatus::ACTIVE,
    ): User {
        $employee = (new Employee)->forceFill([
            'user_id' => $userId,
            'branch_id' => $branchId,
            'role' => $role,
            'status' => $status,
        ]);

        $user = (new User)->forceFill([
            'id' => $userId,
        ]);

        $user->setRelation('employee', $employee);

        return $user;
    }
}
