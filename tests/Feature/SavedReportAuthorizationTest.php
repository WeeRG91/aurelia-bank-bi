<?php

namespace Tests\Feature;

use App\Analytics\Datasets\DatasetKey;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SavedReportAuthorizationTest extends TestCase
{
    public function test_analytical_employee_can_create_saved_reports(): void
    {
        $analyst = $this->user(
            EmployeeRole::BRANCH_ANALYST,
            employeeId: 10,
            branchId: 100,
        );

        $administrator = $this->user(
            EmployeeRole::ADMINISTRATOR,
            employeeId: 20,
        );

        $this->assertTrue(
            Gate::forUser($analyst)->allows(
                'create',
                SavedReport::class,
            ),
        );

        $this->assertFalse(
            Gate::forUser($administrator)->allows(
                'create',
                SavedReport::class,
            ),
        );
    }

    public function test_owner_can_manage_private_saved_report(): void
    {
        $owner = $this->user(
            EmployeeRole::FINANCE_ANALYST,
            employeeId: 10,
        );

        $report = $this->report(ownerEmployeeId: 10);

        foreach ([
            'view',
            'update',
            'delete',
            'restore',
        ] as $ability) {
            $this->assertTrue(
                Gate::forUser($owner)->allows(
                    $ability,
                    $report,
                ),
                "Expected owner to {$ability} their report.",
            );
        }
    }

    public function test_other_employee_cannot_access_private_saved_report(): void
    {
        $otherEmployee = $this->user(
            EmployeeRole::COUNTRY_MANAGER,
            employeeId: 20,
        );

        $report = $this->report(ownerEmployeeId: 10);

        foreach ([
            'view',
            'update',
            'delete',
            'restore',
        ] as $ability) {
            $this->assertFalse(
                Gate::forUser($otherEmployee)->allows(
                    $ability,
                    $report,
                ),
                "Expected non-owner to be denied {$ability}.",
            );
        }
    }

    public function test_saved_reports_cannot_be_permanently_deleted(): void
    {
        $owner = $this->user(
            EmployeeRole::BRANCH_ANALYST,
            employeeId: 10,
            branchId: 100,
        );

        $this->assertFalse(
            Gate::forUser($owner)->allows(
                'forceDelete',
                $this->report(ownerEmployeeId: 10),
            ),
        );
    }

    public function test_inactive_owner_is_denied(): void
    {
        $owner = $this->user(
            EmployeeRole::FINANCE_ANALYST,
            employeeId: 10,
            status: EmployeeStatus::SUSPENDED,
        );

        $report = $this->report(ownerEmployeeId: 10);

        $this->assertFalse(
            Gate::forUser($owner)->allows('view', $report),
        );

        $this->assertFalse(
            Gate::forUser($owner)->allows(
                'create',
                SavedReport::class,
            ),
        );
    }

    public function test_owner_can_export_a_permitted_active_dataset(): void
    {
        $owner = $this->user(
            EmployeeRole::FINANCE_ANALYST,
            employeeId: 10,
        );

        $this->assertTrue(
            Gate::forUser($owner)->allows(
                'export',
                $this->report(ownerEmployeeId: 10),
            ),
        );
    }

    public function test_owner_cannot_export_after_losing_dataset_access(): void
    {
        $administrator = $this->user(
            EmployeeRole::ADMINISTRATOR,
            employeeId: 10,
        );

        $this->assertFalse(
            Gate::forUser($administrator)->allows(
                'export',
                $this->report(ownerEmployeeId: 10),
            ),
        );
    }

    private function user(
        EmployeeRole $role,
        int $employeeId,
        ?int $branchId = null,
        EmployeeStatus $status = EmployeeStatus::ACTIVE,
    ): User {
        $user = (new User)->forceFill([
            'id' => $employeeId + 1_000,
        ]);

        $user->setRelation(
            'employee',
            (new Employee)->forceFill([
                'id' => $employeeId,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'role' => $role,
                'status' => $status,
            ]),
        );

        return $user;
    }

    private function report(
        int $ownerEmployeeId,
    ): SavedReport {
        return (new SavedReport)->forceFill([
            'id' => 500,
            'owner_employee_id' => $ownerEmployeeId,
            'dataset' => DatasetKey::TRANSACTIONS,
        ]);
    }
}
