<?php

namespace Database\Factories;

use App\Enums\EmployeeDepartment;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        $profile = fake()->randomElement([
            [
                'department' => EmployeeDepartment::BRANCH_OPERATIONS,
                'role' => EmployeeRole::BRANCH_ANALYST,
                'job_title' => 'Branch Analyst',
                'requires_branch' => true,
            ],
            [
                'department' => EmployeeDepartment::BRANCH_OPERATIONS,
                'role' => EmployeeRole::BRANCH_MANAGER,
                'job_title' => 'Branch Manager',
                'requires_branch' => true,
            ],
            [
                'department' => EmployeeDepartment::MANAGEMENT,
                'role' => EmployeeRole::COUNTRY_MANAGER,
                'job_title' => 'Country Manager',
                'requires_branch' => false,
            ],
            [
                'department' => EmployeeDepartment::FINANCE,
                'role' => EmployeeRole::FINANCE_ANALYST,
                'job_title' => 'Finance Analyst',
                'requires_branch' => false,
            ],
            [
                'department' => EmployeeDepartment::RISK,
                'role' => EmployeeRole::RISK_ANALYST,
                'job_title' => 'Risk Analyst',
                'requires_branch' => false,
            ],
            [
                'department' => EmployeeDepartment::AUDIT,
                'role' => EmployeeRole::AUDITOR,
                'job_title' => 'Internal Auditor',
                'requires_branch' => false,
            ],
            [
                'department' => EmployeeDepartment::ADMINISTRATION,
                'role' => EmployeeRole::ADMINISTRATOR,
                'job_title' => 'Platform Administrator',
                'requires_branch' => false,
            ],
        ]);

        $status = fake()->randomElement([
            EmployeeStatus::ACTIVE,
            EmployeeStatus::ACTIVE,
            EmployeeStatus::ACTIVE,
            EmployeeStatus::ACTIVE,
            EmployeeStatus::INACTIVE,
            EmployeeStatus::SUSPENDED,
            EmployeeStatus::TERMINATED,
        ]);

        $hiredAt = CarbonImmutable::instance(
            fake()->dateTimeBetween('-20 years', 'now'),
        )->startOfDay();

        $terminatedAt = $status === EmployeeStatus::TERMINATED
            ? CarbonImmutable::instance(
                fake()->dateTimeBetween($hiredAt, 'now'),
            )->startOfDay()
            : null;

        return [
            'user_id' => User::factory(),
            'branch_id' => $profile['requires_branch']
                ? Branch::factory()
                : (fake()->boolean(25) ? Branch::factory() : null),
            'employee_number' => sprintf(
                'EMP-%08d',
                fake()->unique()->numberBetween(1, 99_999_999),
            ),
            'department' => $profile['department'],
            'job_title' => $profile['job_title'],
            'role' => $profile['role'],
            'hired_at' => $hiredAt->format('Y-m-d'),
            'terminated_at' => $terminatedAt?->format('Y-m-d'),
            'status' => $status,
        ];
    }
}
