<?php

namespace Tests\Feature;

use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use Tests\TestCase;

final class HorizonAuthorizationTest extends TestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/horizon')
            ->assertRedirect('/login');
    }

    public function test_active_administrator_can_view_horizon(): void
    {
        $this->actingAs(
            $this->user(EmployeeRole::ADMINISTRATOR),
        )
            ->get('/horizon')
            ->assertOk()
            ->assertSee('Horizon');
    }

    public function test_operational_employee_cannot_view_horizon(): void
    {
        $this->actingAs(
            $this->user(EmployeeRole::BRANCH_ANALYST),
        )
            ->get('/horizon')
            ->assertForbidden();
    }

    public function test_inactive_administrator_cannot_view_horizon(): void
    {
        $this->actingAs(
            $this->user(
                EmployeeRole::ADMINISTRATOR,
                EmployeeStatus::SUSPENDED,
            ),
        )
            ->get('/horizon')
            ->assertForbidden();
    }

    private function user(
        EmployeeRole $role,
        EmployeeStatus $status = EmployeeStatus::ACTIVE,
    ): User {
        $user = (new User)->forceFill([
            'id' => 1_000,
            'name' => 'Horizon test user',
            'email' => $role->value.'@aurelia.test',
        ]);

        $user->setRelation(
            'employee',
            (new Employee)->forceFill([
                'id' => 2_000,
                'user_id' => $user->getKey(),
                'branch_id' => null,
                'role' => $role,
                'status' => $status,
            ]),
        );

        return $user;
    }
}
