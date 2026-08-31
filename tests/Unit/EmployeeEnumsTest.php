<?php

namespace Tests\Unit;

use App\Enums\EmployeeDepartment;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use PHPUnit\Framework\TestCase;

class EmployeeEnumsTest extends TestCase
{
    public function test_employee_departments_have_stable_values(): void
    {
        $this->assertSame(
            [
                'branch_operations',
                'finance',
                'risk',
                'audit',
                'data_analytics',
                'administration',
                'management',
            ],
            array_column(EmployeeDepartment::cases(), 'value'),
        );
    }

    public function test_employee_roles_have_stable_values(): void
    {
        $this->assertSame(
            [
                'branch_analyst',
                'branch_manager',
                'country_manager',
                'finance_analyst',
                'risk_analyst',
                'auditor',
                'administrator',
            ],
            array_column(EmployeeRole::cases(), 'value'),
        );
    }

    public function test_employee_statuses_have_stable_values(): void
    {
        $this->assertSame(
            ['active', 'inactive', 'suspended', 'terminated'],
            array_column(EmployeeStatus::cases(), 'value'),
        );
    }
}
