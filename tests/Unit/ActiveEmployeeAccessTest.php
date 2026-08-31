<?php

namespace Tests\Unit;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class ActiveEmployeeAccessTest extends TestCase
{
    public function test_active_employee_can_access_the_platform(): void
    {
        $user = new User;

        $user->setRelation(
            'employee',
            new Employee([
                'status' => EmployeeStatus::ACTIVE,
            ]),
        );

        $this->assertTrue($user->isActiveEmployee());
    }

    public function test_user_without_employee_profile_cannot_access(): void
    {
        $user = new User;

        $user->setRelation('employee', null);

        $this->assertFalse($user->isActiveEmployee());
    }

    public function test_non_active_employees_cannot_access(): void
    {
        foreach ([
            EmployeeStatus::INACTIVE,
            EmployeeStatus::SUSPENDED,
            EmployeeStatus::TERMINATED,
        ] as $status) {
            $user = new User;

            $user->setRelation(
                'employee',
                new Employee(['status' => $status]),
            );

            $this->assertFalse(
                $user->isActiveEmployee(),
                "Expected {$status->value} employee to be denied.",
            );
        }
    }
}
