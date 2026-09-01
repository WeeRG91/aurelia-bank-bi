<?php

namespace App\Analytics\Queries\Authorization;

use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetKey;
use App\Enums\EmployeeRole;
use App\Models\User;

final readonly class DatasetRowScopeResolver
{
    public function __construct(
        private DatasetAccess $datasetAccess,
    ) {}

    public function resolve(
        User $user,
        DatasetKey $dataset,
    ): DatasetRowScope {
        if (! $this->datasetAccess->roleAllows($user, $dataset)) {
            return DatasetRowScope::denied();
        }

        $employee = $user->employee;

        if ($employee === null) {
            return DatasetRowScope::denied();
        }

        return match ($employee->role) {
            EmployeeRole::BRANCH_ANALYST,
            EmployeeRole::BRANCH_MANAGER => $employee->branch_id === null
                ? DatasetRowScope::denied()
                : DatasetRowScope::branch($employee->branch_id),

            EmployeeRole::FINANCE_ANALYST,
            EmployeeRole::RISK_ANALYST,
            EmployeeRole::AUDITOR => DatasetRowScope::unrestricted(),

            EmployeeRole::COUNTRY_MANAGER,
            EmployeeRole::ADMINISTRATOR => DatasetRowScope::denied(),
        };
    }
}
