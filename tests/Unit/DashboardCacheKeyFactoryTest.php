<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardCacheKeyFactory;
use App\Analytics\Dashboards\DashboardPeriod;
use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Queries\Authorization\DatasetRowScopeResolver;
use App\Analytics\Time\ReportingTimezone;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DashboardCacheKeyFactoryTest extends TestCase
{
    public function test_equivalent_authorization_contexts_share_a_key(): void
    {
        $first = $this->factory()->forUser(
            $this->user(
                EmployeeRole::BRANCH_ANALYST,
                branchId: 42,
                employeeId: 10,
            ),
            DashboardPeriod::LAST_30_DAYS,
            $this->timezone(),
            $this->now(),
        );

        $second = $this->factory()->forUser(
            $this->user(
                EmployeeRole::BRANCH_ANALYST,
                branchId: 42,
                employeeId: 20,
            ),
            DashboardPeriod::LAST_30_DAYS,
            $this->timezone(),
            $this->now(),
        );

        $this->assertSame($first, $second);

        $this->assertMatchesRegularExpression(
            '/^analytics:dashboard:v1:[a-f0-9]{64}$/',
            (string) $first,
        );
    }

    public function test_different_branches_never_share_a_key(): void
    {
        $first = $this->keyForBranch(42);
        $second = $this->keyForBranch(84);

        $this->assertNotSame($first, $second);
    }

    public function test_different_roles_never_share_a_key(): void
    {
        $finance = $this->factory()->forUser(
            $this->user(EmployeeRole::FINANCE_ANALYST),
            DashboardPeriod::LAST_30_DAYS,
            $this->timezone(),
            $this->now(),
        );

        $risk = $this->factory()->forUser(
            $this->user(EmployeeRole::RISK_ANALYST),
            DashboardPeriod::LAST_30_DAYS,
            $this->timezone(),
            $this->now(),
        );

        $this->assertNotSame($finance, $risk);
    }

    public function test_period_and_reporting_date_change_the_key(): void
    {
        $user = $this->user(
            EmployeeRole::BRANCH_ANALYST,
            branchId: 42,
        );

        $sevenDays = $this->factory()->forUser(
            $user,
            DashboardPeriod::LAST_7_DAYS,
            $this->timezone(),
            $this->now(),
        );

        $thirtyDays = $this->factory()->forUser(
            $user,
            DashboardPeriod::LAST_30_DAYS,
            $this->timezone(),
            $this->now(),
        );

        $nextReportingDay = $this->factory()->forUser(
            $user,
            DashboardPeriod::LAST_7_DAYS,
            $this->timezone(),
            new DateTimeImmutable(
                '2026-10-07T22:30:00+00:00',
            ),
        );

        $this->assertNotSame($sevenDays, $thirtyDays);
        $this->assertNotSame($sevenDays, $nextReportingDay);
    }

    public function test_denied_scope_is_not_cacheable(): void
    {
        $key = $this->factory()->forUser(
            $this->user(EmployeeRole::ADMINISTRATOR),
            DashboardPeriod::LAST_30_DAYS,
            $this->timezone(),
            $this->now(),
        );

        $this->assertNull($key);
    }

    private function keyForBranch(int $branchId): ?string
    {
        return $this->factory()->forUser(
            $this->user(
                EmployeeRole::BRANCH_ANALYST,
                branchId: $branchId,
            ),
            DashboardPeriod::LAST_30_DAYS,
            $this->timezone(),
            $this->now(),
        );
    }

    private function factory(): DashboardCacheKeyFactory
    {
        return new DashboardCacheKeyFactory(
            new DatasetRowScopeResolver(
                new DatasetAccess(
                    new DatasetRegistry,
                ),
            ),
        );
    }

    private function user(
        EmployeeRole $role,
        ?int $branchId = null,
        int $employeeId = 10,
    ): User {
        $employee = (new Employee)->forceFill([
            'id' => $employeeId,
            'branch_id' => $branchId,
            'role' => $role,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $user = (new User)->forceFill([
            'id' => $employeeId + 1_000,
        ]);

        $user->setRelation('employee', $employee);

        return $user;
    }

    private function timezone(): ReportingTimezone
    {
        return new ReportingTimezone('Europe/Luxembourg');
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            '2026-10-07T12:00:00+00:00',
        );
    }
}
