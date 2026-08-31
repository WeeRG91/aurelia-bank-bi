<?php

namespace Tests\Feature;

use App\Enums\EmployeeDepartment;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class EmployeeFactoryTest extends TestCase
{
    public function test_it_builds_a_coherent_synthetic_employee(): void
    {
        $employee = Employee::factory()->make([
            'user_id' => 1,
            'branch_id' => 1,
        ]);

        $this->assertMatchesRegularExpression(
            '/^EMP-\d{8}$/',
            $employee->employee_number,
        );
        $this->assertInstanceOf(
            EmployeeDepartment::class,
            $employee->department,
        );
        $this->assertInstanceOf(EmployeeRole::class, $employee->role);
        $this->assertInstanceOf(EmployeeStatus::class, $employee->status);
        $this->assertNotSame('', trim($employee->job_title));
        $this->assertInstanceOf(
            CarbonImmutable::class,
            $employee->hired_at,
        );

        if ($employee->status === EmployeeStatus::TERMINATED) {
            $this->assertInstanceOf(
                CarbonImmutable::class,
                $employee->terminated_at,
            );
            $this->assertTrue(
                $employee->hired_at->lessThanOrEqualTo(
                    $employee->terminated_at,
                ),
            );
        } else {
            $this->assertNull($employee->terminated_at);
        }
    }

    public function test_models_define_employee_relationships(): void
    {
        $employee = new Employee;
        $user = new User;
        $branch = new Branch;

        $this->assertSame(
            'user_id',
            $employee->user()->getForeignKeyName(),
        );
        $this->assertSame(
            'branch_id',
            $employee->branch()->getForeignKeyName(),
        );
        $this->assertSame(
            'user_id',
            $user->employee()->getForeignKeyName(),
        );
        $this->assertSame(
            'branch_id',
            $branch->employees()->getForeignKeyName(),
        );
    }
}
