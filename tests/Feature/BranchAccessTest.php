<?php

namespace Tests\Feature;

use App\Authorization\BranchAccess;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use Tests\TestCase;

class BranchAccessTest extends TestCase
{
    public function test_branch_roles_are_restricted_to_their_assigned_branch(): void
    {
        foreach ([
            EmployeeRole::BRANCH_ANALYST,
            EmployeeRole::BRANCH_MANAGER,
        ] as $role) {
            $query = app(BranchAccess::class)
                ->queryFor($this->user($role, 42));

            $this->assertStringContainsString(
                'where "branches"."id" = ?',
                strtolower($query->toSql()),
            );

            $this->assertSame([42], $query->getBindings());
        }
    }

    public function test_head_office_roles_can_query_all_branches(): void
    {
        foreach ([
            EmployeeRole::COUNTRY_MANAGER,
            EmployeeRole::FINANCE_ANALYST,
            EmployeeRole::RISK_ANALYST,
            EmployeeRole::AUDITOR,
            EmployeeRole::ADMINISTRATOR,
        ] as $role) {
            $query = app(BranchAccess::class)
                ->queryFor($this->user($role));

            $this->assertStringNotContainsString(
                ' where ',
                strtolower($query->toSql()),
            );

            $this->assertSame([], $query->getBindings());
        }
    }

    public function test_invalid_employee_states_produce_an_empty_query(): void
    {
        $suspendedAdministrator = app(BranchAccess::class)
            ->queryFor(
                $this->user(
                    EmployeeRole::ADMINISTRATOR,
                    null,
                    EmployeeStatus::SUSPENDED,
                ),
            );

        $unassignedBranchAnalyst = app(BranchAccess::class)
            ->queryFor(
                $this->user(EmployeeRole::BRANCH_ANALYST),
            );

        $this->assertStringContainsString(
            '1 = 0',
            $suspendedAdministrator->toSql(),
        );

        $this->assertStringContainsString(
            '1 = 0',
            $unassignedBranchAnalyst->toSql(),
        );
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
