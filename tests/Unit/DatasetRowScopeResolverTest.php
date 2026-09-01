<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Queries\Authorization\DatasetRowScopeResolver;
use App\Analytics\Queries\Authorization\RowScopeType;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class DatasetRowScopeResolverTest extends TestCase
{
    public function test_branch_roles_are_restricted_to_their_assigned_branch(): void
    {
        foreach ([
            EmployeeRole::BRANCH_ANALYST,
            EmployeeRole::BRANCH_MANAGER,
        ] as $role) {
            $scope = $this->resolver()->resolve(
                $this->user($role, branchId: 42),
                DatasetKey::TRANSACTIONS,
            );

            $this->assertSame(RowScopeType::BRANCH, $scope->type);
            $this->assertSame(42, $scope->branchId);
        }
    }

    public function test_global_analytical_roles_receive_unrestricted_row_scope(): void
    {
        foreach ([
            EmployeeRole::FINANCE_ANALYST,
            EmployeeRole::RISK_ANALYST,
            EmployeeRole::AUDITOR,
        ] as $role) {
            $scope = $this->resolver()->resolve(
                $this->user($role),
                DatasetKey::TRANSACTIONS,
            );

            $this->assertSame(
                RowScopeType::UNRESTRICTED,
                $scope->type,
            );

            $this->assertNull($scope->branchId);
        }
    }

    public function test_branch_role_without_a_branch_is_denied(): void
    {
        $scope = $this->resolver()->resolve(
            $this->user(EmployeeRole::BRANCH_ANALYST),
            DatasetKey::TRANSACTIONS,
        );

        $this->assertSame(RowScopeType::DENIED, $scope->type);
    }

    public function test_country_manager_fails_closed_until_country_assignment_exists(): void
    {
        $scope = $this->resolver()->resolve(
            $this->user(EmployeeRole::COUNTRY_MANAGER),
            DatasetKey::TRANSACTIONS,
        );

        $this->assertSame(RowScopeType::DENIED, $scope->type);
    }

    public function test_administrator_has_no_business_data_scope(): void
    {
        $scope = $this->resolver()->resolve(
            $this->user(EmployeeRole::ADMINISTRATOR),
            DatasetKey::TRANSACTIONS,
        );

        $this->assertSame(RowScopeType::DENIED, $scope->type);
    }

    public function test_role_without_dataset_permission_is_denied(): void
    {
        $scope = $this->resolver()->resolve(
            $this->user(
                EmployeeRole::BRANCH_ANALYST,
                branchId: 42,
            ),
            DatasetKey::CUSTOMER_OVERVIEW,
        );

        $this->assertSame(RowScopeType::DENIED, $scope->type);
    }

    public function test_inactive_employee_is_denied(): void
    {
        $scope = $this->resolver()->resolve(
            $this->user(
                EmployeeRole::AUDITOR,
                status: EmployeeStatus::SUSPENDED,
            ),
            DatasetKey::TRANSACTIONS,
        );

        $this->assertSame(RowScopeType::DENIED, $scope->type);
    }

    private function resolver(): DatasetRowScopeResolver
    {
        return new DatasetRowScopeResolver(
            new DatasetAccess(new DatasetRegistry),
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
