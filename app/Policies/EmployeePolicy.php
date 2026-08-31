<?php

namespace App\Policies;

use App\Enums\EmployeeRole;
use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
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
        $role = $user->employee?->role;

        return in_array($role, [
            EmployeeRole::BRANCH_MANAGER,
            EmployeeRole::COUNTRY_MANAGER,
            EmployeeRole::AUDITOR,
            EmployeeRole::ADMINISTRATOR,
        ], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Employee $employee): bool
    {
        $viewer = $user->employee;

        if ($viewer === null) {
            return false;
        }

        if ($employee->user_id === $user->getKey()) {
            return true;
        }

        return match ($viewer->role) {
            EmployeeRole::COUNTRY_MANAGER,
            EmployeeRole::AUDITOR,
            EmployeeRole::ADMINISTRATOR => true,

            EmployeeRole::BRANCH_MANAGER => $viewer->branch_id !== null
                && $viewer->branch_id === $employee->branch_id,

            default => false,
        };
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->employee?->role === EmployeeRole::ADMINISTRATOR;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Employee $employee): bool
    {
        return $user->employee?->role === EmployeeRole::ADMINISTRATOR;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Employee $employee): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Employee $employee): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Employee $employee): bool
    {
        return false;
    }
}
