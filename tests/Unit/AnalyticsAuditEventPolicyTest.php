<?php

namespace Tests\Unit;

use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\AnalyticsAuditEvent;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

final class AnalyticsAuditEventPolicyTest extends TestCase
{
    public function test_auditor_and_administrator_can_view_audit_trail(): void
    {
        foreach ([
            EmployeeRole::AUDITOR,
            EmployeeRole::ADMINISTRATOR,
        ] as $role) {
            $this->assertTrue(
                Gate::forUser($this->user($role))->allows(
                    'viewAny',
                    AnalyticsAuditEvent::class,
                ),
            );
        }
    }

    public function test_operational_roles_cannot_view_audit_trail(): void
    {
        foreach ([
            EmployeeRole::BRANCH_ANALYST,
            EmployeeRole::BRANCH_MANAGER,
            EmployeeRole::COUNTRY_MANAGER,
            EmployeeRole::FINANCE_ANALYST,
            EmployeeRole::RISK_ANALYST,
        ] as $role) {
            $this->assertFalse(
                Gate::forUser($this->user($role))->allows(
                    'viewAny',
                    AnalyticsAuditEvent::class,
                ),
            );
        }
    }

    public function test_audit_events_cannot_be_mutated_through_policy(): void
    {
        $administrator = $this->user(
            EmployeeRole::ADMINISTRATOR,
        );

        $event = new AnalyticsAuditEvent;

        foreach ([
            'create',
            'update',
            'delete',
            'restore',
            'forceDelete',
        ] as $ability) {
            $arguments = $ability === 'create'
                ? AnalyticsAuditEvent::class
                : $event;

            $this->assertFalse(
                Gate::forUser($administrator)->allows(
                    $ability,
                    $arguments,
                ),
            );
        }
    }

    public function test_inactive_privileged_employee_is_denied(): void
    {
        $auditor = $this->user(
            EmployeeRole::AUDITOR,
            EmployeeStatus::SUSPENDED,
        );

        $this->assertFalse(
            Gate::forUser($auditor)->allows(
                'viewAny',
                AnalyticsAuditEvent::class,
            ),
        );
    }

    private function user(
        EmployeeRole $role,
        EmployeeStatus $status = EmployeeStatus::ACTIVE,
    ): User {
        $user = (new User)->forceFill([
            'id' => 1_000,
        ]);

        $user->setRelation(
            'employee',
            (new Employee)->forceFill([
                'id' => 10,
                'user_id' => $user->getKey(),
                'role' => $role,
                'status' => $status,
            ]),
        );

        return $user;
    }
}
