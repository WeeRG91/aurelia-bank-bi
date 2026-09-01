<?php

namespace App\Analytics\Datasets;

use App\Enums\EmployeeRole;
use App\Models\User;

final readonly class DatasetAccess
{
    public function __construct(
        private DatasetRegistry $registry,
    ) {}

    /**
     * @return list<DatasetKey>
     */
    public function permittedKeysFor(User $user): array
    {
        $employee = $user->employee;

        if (! $user->isActiveEmployee() || $employee === null) {
            return [];
        }

        return match ($employee->role) {
            EmployeeRole::BRANCH_ANALYST => [
                DatasetKey::ACCOUNT_BALANCES,
                DatasetKey::TRANSACTIONS,
                DatasetKey::BRANCH_PERFORMANCE,
            ],

            EmployeeRole::BRANCH_MANAGER,
            EmployeeRole::COUNTRY_MANAGER,
            EmployeeRole::AUDITOR => DatasetKey::cases(),

            EmployeeRole::FINANCE_ANALYST => [
                DatasetKey::ACCOUNT_BALANCES,
                DatasetKey::TRANSACTIONS,
                DatasetKey::LOANS,
                DatasetKey::LOAN_REPAYMENTS,
                DatasetKey::BRANCH_PERFORMANCE,
            ],

            EmployeeRole::RISK_ANALYST => [
                DatasetKey::CUSTOMER_OVERVIEW,
                DatasetKey::TRANSACTIONS,
                DatasetKey::CARD_ACTIVITY,
                DatasetKey::LOANS,
                DatasetKey::LOAN_REPAYMENTS,
                DatasetKey::BRANCH_PERFORMANCE,
            ],

            EmployeeRole::ADMINISTRATOR => [],
        };
    }

    public function roleAllows(
        User $user,
        DatasetKey|string $identifier,
    ): bool {
        $definition = $this->registry->find($identifier);

        if ($definition === null) {
            return false;
        }

        return in_array(
            $definition->key,
            $this->permittedKeysFor($user),
            true,
        );
    }

    public function canUse(
        User $user,
        DatasetKey|string $identifier,
    ): bool {
        $definition = $this->registry->find($identifier);

        return $definition !== null
            && $definition->status === DatasetStatus::ACTIVE
            && $this->roleAllows($user, $definition->key);
    }

    /**
     * @return list<DatasetDefinition>
     */
    public function discoverableTo(User $user): array
    {
        return array_values(
            array_filter(
                $this->registry->active(),
                fn (DatasetDefinition $definition): bool => $this->roleAllows($user, $definition->key)
            ),
        );
    }

    public function canInspectRegistry(User $user): bool
    {
        return $user->isActiveEmployee()
            && $user->employee?->role === EmployeeRole::ADMINISTRATOR;
    }

    /**
     * @return list<DatasetDefinition>
     */
    public function catalogFor(User $user): array
    {
        if ($this->canInspectRegistry($user)) {
            return $this->registry->all();
        }

        return $this->discoverableTo($user);
    }

    public function canViewDefinition(
        User $user,
        DatasetKey|string $identifier,
    ): bool {
        if ($this->registry->find($identifier) === null) {
            return false;
        }

        return $this->canInspectRegistry($user)
            || $this->canUse($user, $identifier);
    }
}
