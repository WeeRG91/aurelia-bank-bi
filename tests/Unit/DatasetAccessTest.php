<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetDefinition;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\DatasetStatus;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class DatasetAccessTest extends TestCase
{
    public function test_roles_receive_the_approved_dataset_permissions(): void
    {
        $expectations = [
            EmployeeRole::BRANCH_ANALYST->value => [
                'account_balances',
                'transactions',
                'branch_performance',
            ],
            EmployeeRole::BRANCH_MANAGER->value => [
                'customer_overview',
                'account_balances',
                'transactions',
                'card_activity',
                'loans',
                'loan_repayments',
                'branch_performance',
            ],
            EmployeeRole::COUNTRY_MANAGER->value => [
                'customer_overview',
                'account_balances',
                'transactions',
                'card_activity',
                'loans',
                'loan_repayments',
                'branch_performance',
            ],
            EmployeeRole::FINANCE_ANALYST->value => [
                'account_balances',
                'transactions',
                'loans',
                'loan_repayments',
                'branch_performance',
            ],
            EmployeeRole::RISK_ANALYST->value => [
                'customer_overview',
                'transactions',
                'card_activity',
                'loans',
                'loan_repayments',
                'branch_performance',
            ],
            EmployeeRole::AUDITOR->value => [
                'customer_overview',
                'account_balances',
                'transactions',
                'card_activity',
                'loans',
                'loan_repayments',
                'branch_performance',
            ],
            EmployeeRole::ADMINISTRATOR->value => [],
        ];

        $access = new DatasetAccess(new DatasetRegistry);

        foreach ($expectations as $roleValue => $expectedKeys) {
            $role = EmployeeRole::from($roleValue);

            $actualKeys = array_map(
                fn (DatasetKey $key): string => $key->value,
                $access->permittedKeysFor(
                    $this->user($role),
                ),
            );

            $this->assertSame(
                $expectedKeys,
                $actualKeys,
                "Unexpected dataset permissions for {$role->value}.",
            );
        }
    }

    public function test_active_datasets_require_both_status_and_role_permission(): void
    {
        $registry = new DatasetRegistry([
            new DatasetDefinition(
                key: DatasetKey::CUSTOMER_OVERVIEW,
                label: 'Customer Overview',
                description: 'Customer analytics.',
                status: DatasetStatus::ACTIVE,
            ),
            new DatasetDefinition(
                key: DatasetKey::TRANSACTIONS,
                label: 'Transactions',
                description: 'Transaction analytics.',
                status: DatasetStatus::ACTIVE,
            ),
        ]);

        $access = new DatasetAccess($registry);
        $analyst = $this->user(EmployeeRole::BRANCH_ANALYST);

        $this->assertTrue(
            $access->canUse($analyst, DatasetKey::TRANSACTIONS),
        );

        $this->assertFalse(
            $access->canUse($analyst, DatasetKey::CUSTOMER_OVERVIEW),
        );

        $this->assertSame(
            ['transactions'],
            array_map(
                fn (DatasetDefinition $definition): string => $definition->key->value,
                $access->discoverableTo($analyst),
            ),
        );
    }

    public function test_administrator_can_inspect_drafts_but_cannot_use_business_datasets(): void
    {
        $registry = new DatasetRegistry;
        $access = new DatasetAccess($registry);
        $administrator = $this->user(EmployeeRole::ADMINISTRATOR);

        $this->assertTrue(
            $access->canInspectRegistry($administrator),
        );

        $this->assertCount(
            7,
            $access->catalogFor($administrator),
        );

        $this->assertFalse(
            $access->canUse(
                $administrator,
                DatasetKey::TRANSACTIONS,
            ),
        );

        $this->assertTrue(
            $access->canViewDefinition(
                $administrator,
                DatasetKey::TRANSACTIONS,
            ),
        );
    }

    public function test_inactive_employee_has_no_dataset_access(): void
    {
        $access = new DatasetAccess(new DatasetRegistry);

        $auditor = $this->user(
            EmployeeRole::AUDITOR,
            EmployeeStatus::SUSPENDED,
        );

        $this->assertSame([], $access->permittedKeysFor($auditor));
        $this->assertSame([], $access->catalogFor($auditor));
        $this->assertFalse($access->canInspectRegistry($auditor));
    }

    public function test_arbitrary_dataset_identifier_is_denied(): void
    {
        $access = new DatasetAccess(new DatasetRegistry);
        $auditor = $this->user(EmployeeRole::AUDITOR);

        $this->assertFalse(
            $access->roleAllows(
                $auditor,
                'users_join_password_reset_tokens',
            ),
        );

        $this->assertFalse(
            $access->canUse(
                $auditor,
                'users_join_password_reset_tokens',
            ),
        );

        $this->assertFalse(
            $access->canViewDefinition(
                $auditor,
                'users_join_password_reset_tokens',
            ),
        );
    }

    private function user(
        EmployeeRole $role,
        EmployeeStatus $status = EmployeeStatus::ACTIVE,
    ): User {
        $employee = (new Employee)->forceFill([
            'branch_id' => $role === EmployeeRole::BRANCH_MANAGER
                ? 100
                : null,
            'role' => $role,
            'status' => $status,
        ]);

        $user = new User;
        $user->setRelation('employee', $employee);

        return $user;
    }
}
