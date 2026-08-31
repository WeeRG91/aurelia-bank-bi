<?php

namespace App\Policies;

use App\Enums\EmployeeRole;
use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function before(User $user): ?bool
    {
        if (! $user->isActiveEmployee()) {
            return false;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Branch $branch): bool
    {
        $employee = $user->employee;

        if ($employee === null) {
            return false;
        }

        return match ($employee->role) {
            EmployeeRole::COUNTRY_MANAGER,
            EmployeeRole::FINANCE_ANALYST,
            EmployeeRole::RISK_ANALYST,
            EmployeeRole::AUDITOR,
            EmployeeRole::ADMINISTRATOR => true,

            EmployeeRole::BRANCH_ANALYST,
            EmployeeRole::BRANCH_MANAGER => $employee->branch_id !== null
                && $employee->branch_id === $branch->getKey(),
        };
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Branch $branch): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Branch $branch): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Branch $branch): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Branch $branch): bool
    {
        return false;
    }
}
