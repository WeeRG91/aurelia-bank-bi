<?php

namespace App\Authorization;

use App\Enums\EmployeeRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class BranchAccess
{
    /**
     * @return Builder<Branch>
     */
    public function queryFor(User $user): Builder
    {
        $query = Branch::query();

        if (! $user->isActiveEmployee()) {
            return $this->denyAll($query);
        }

        if ($this->canViewAll($user)) {
            return $query;
        }

        $branchId = $this->assignedBranchId($user);

        if ($branchId === null) {
            return $this->denyAll($query);
        }

        return $query->whereKey($branchId);
    }

    public function canView(User $user, Branch $branch): bool
    {
        if (! $user->isActiveEmployee()) {
            return false;
        }

        if ($this->canViewAll($user)) {
            return true;
        }

        $branchId = $this->assignedBranchId($user);

        return $branchId !== null
            && $branchId === $branch->getKey();
    }

    private function canViewAll(User $user): bool
    {
        return in_array($user->employee?->role, [
            EmployeeRole::COUNTRY_MANAGER,
            EmployeeRole::FINANCE_ANALYST,
            EmployeeRole::RISK_ANALYST,
            EmployeeRole::AUDITOR,
            EmployeeRole::ADMINISTRATOR,
        ], true);
    }

    private function assignedBranchId(User $user): ?int
    {
        $employee = $user->employee;

        if ($employee === null) {
            return null;
        }

        if (! in_array($employee->role, [
            EmployeeRole::BRANCH_ANALYST,
            EmployeeRole::BRANCH_MANAGER,
        ], true)) {
            return null;
        }

        return $employee->branch_id;
    }

    /**
     * @param  Builder<Branch>  $query
     * @return Builder<Branch>
     */
    private function denyAll(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }
}
