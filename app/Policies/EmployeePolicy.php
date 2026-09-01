<?php

namespace App\Policies;

use App\Authorization\EmployeeAccess;
use App\Enums\EmployeeRole;
use App\Models\Employee;
use App\Models\User;

readonly class EmployeePolicy
{
    public function __construct(
        private EmployeeAccess $employeeAccess,
    ) {}

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
        return $this->employeeAccess->canViewDirectory($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Employee $employee): bool
    {
        return $this->employeeAccess->canView($user, $employee);
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
