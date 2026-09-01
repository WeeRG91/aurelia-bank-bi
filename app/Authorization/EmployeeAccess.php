<?php

namespace App\Authorization;

use App\Enums\EmployeeRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class EmployeeAccess
{
    public function canViewDirectory(User $user): bool
    {
        if (! $user->isActiveEmployee()) {
            return false;
        }

        $employee = $user->employee;

        if ($employee === null) {
            return false;
        }

        return match ($employee->role) {
            EmployeeRole::BRANCH_MANAGER => $employee->branch_id !== null,

            EmployeeRole::COUNTRY_MANAGER,
            EmployeeRole::AUDITOR,
            EmployeeRole::ADMINISTRATOR => true,

            default => false,
        };
    }

    /**
     * @return Builder<Employee>
     */
    public function queryFor(User $user): Builder
    {
        $query = Employee::query();
        $viewer = $user->employee;

        if (! $user->isActiveEmployee() || $viewer === null) {
            return $this->denyAll($query);
        }

        if ($this->canViewAll($user)) {
            return $query;
        }

        if (
            $viewer->role === EmployeeRole::BRANCH_MANAGER &&
            $viewer->branch_id !== null
        ) {
            return $query->where('employees.branch_id', $viewer->branch_id);
        }

        return $query->where('employees.user_id', $user->getKey());
    }

    public function canView(User $user, Employee $employee): bool
    {
        $viewer = $user->employee;

        if (! $user->isActiveEmployee() || $viewer === null) {
            return false;
        }

        if ($employee->user_id === $user->getKey()) {
            return true;
        }

        if ($this->canViewAll($user)) {
            return true;
        }

        return $viewer->role === EmployeeRole::BRANCH_MANAGER &&
            $viewer->branch_id !== null &&
            $viewer->branch_id === $employee->branch_id;
    }

    private function canViewAll(User $user): bool
    {
        return in_array($user->employee?->role, [
            EmployeeRole::COUNTRY_MANAGER,
            EmployeeRole::AUDITOR,
            EmployeeRole::ADMINISTRATOR,
        ], true);
    }

    /**
     * @param  Builder<Employee>  $query
     * @return Builder<Employee>
     */
    private function denyAll(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }
}
