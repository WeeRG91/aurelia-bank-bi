<?php

namespace Tests\Feature;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditRecorder;
use App\Analytics\Auditing\AuditSource;
use App\Enums\EmployeeDepartment;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\AnalyticsAuditEvent;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Tests\TestCase;

final class DashboardControllerTest extends TestCase
{
    public function test_administrator_receives_safe_empty_dashboard(): void
    {
        $database = $this->createMock(DatabaseManager::class);

        $database->expects($this->never())
            ->method('connection');

        $this->app->instance(
            DatabaseManager::class,
            $database,
        );

        $audit = $this->createMock(AuditRecorder::class);

        $audit->expects($this->once())
            ->method('record')
            ->with(
                AuditAction::DASHBOARD_VIEWED,
                AuditOutcome::SUCCEEDED,
                AuditSource::WEB,
                $this->isInstanceOf(Employee::class),
                null,
                null,
                null,
                $this->callback(
                    static function (AuditContext $context): bool {
                        $values = $context->toArray();

                        return is_int($values['duration_ms'])
                            && $values['duration_ms'] >= 0
                            && $values['period'] === 'last_7_days'
                            && $values['widget_count'] === 0;
                    },
                ),
                $this->callback(
                    static fn (?string $requestId): bool => is_string(
                        $requestId,
                    ) && preg_match(
                        '/^[0-9a-f-]{36}$/',
                        $requestId,
                    ) === 1,
                ),
                '127.0.0.1',
                $this->anything(),
            )
            ->willReturn(new AnalyticsAuditEvent);

        $this->app->instance(
            AuditRecorder::class,
            $audit,
        );

        $this->actingAs($this->administrator())
            ->get(route('dashboard', [
                'period' => 'last_7_days',
            ]))
            ->assertOk()
            ->assertSee('analytics-dashboard-bootstrap')
            ->assertSee(
                '"selectedPeriod":"last_7_days"',
                false,
            )
            ->assertSee('"widgets":[]', false);
    }

    private function administrator(): User
    {
        $employee = (new Employee)->forceFill([
            'id' => 10,
            'user_id' => 1_000,
            'branch_id' => null,
            'employee_number' => 'EMP-99999999',
            'department' => EmployeeDepartment::ADMINISTRATION,
            'role' => EmployeeRole::ADMINISTRATOR,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $employee->setRelation('branch', null);

        $user = (new User)->forceFill([
            'id' => 1_000,
            'name' => 'Aurelia Local Administrator',
        ]);

        $user->setRelation('employee', $employee);

        return $user;
    }
}
