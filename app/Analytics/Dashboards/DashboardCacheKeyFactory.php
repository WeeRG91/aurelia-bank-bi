<?php

namespace App\Analytics\Dashboards;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Queries\Authorization\DatasetRowScopeResolver;
use App\Analytics\Queries\Authorization\RowScopeType;
use App\Analytics\Time\ReportingTimezone;
use App\Enums\EmployeeRole;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateInvalidTimeZoneException;
use DateTimeImmutable;

final readonly class DashboardCacheKeyFactory
{
    private const int VERSION = 1;

    public function __construct(
        private DatasetRowScopeResolver $scopeResolver,
    ) {}

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function forUser(
        User $user,
        DashboardPeriod $period,
        ReportingTimezone $reportingTimezone,
        DateTimeImmutable $now
    ): ?string {
        $employee = $user->employee;

        if (
            ! $employee instanceof Employee ||
            ! $employee->role instanceof EmployeeRole
        ) {
            return null;
        }

        $scope = $this->scopeResolver->resolve(
            $user,
            DatasetKey::TRANSACTIONS,
        );

        if ($scope->type === RowScopeType::DENIED) {
            return null;
        }

        $reportingDate = CarbonImmutable::instance($now)
            ->setTimezone($reportingTimezone->toDateTimeZone())
            ->toDateString();

        $components = implode('|', [
            'version='.self::VERSION,
            'dataset='.DatasetKey::TRANSACTIONS->value,
            'role='.$employee->role->value,
            'scope='.$scope->type->value,
            'branch='.$scope->branchId,
            'period='.$period->value,
            'timezone='.$reportingTimezone->name,
            'date='.$reportingDate,
        ]);

        return sprintf(
            'analytics:dashboard:v%d:%s',
            self::VERSION,
            hash('sha256', $components),
        );
    }
}
