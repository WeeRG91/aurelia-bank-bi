<?php

namespace Tests\Feature;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditRecorder;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\AnalyticsAuditEvent;
use App\Models\Employee;
use App\Models\User;
use Tests\TestCase;

final class DatasetFieldMetadataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $audit = $this->createMock(AuditRecorder::class);

        $audit->expects($this->once())
            ->method('record')
            ->willReturnCallback(
                function (
                    AuditAction $action,
                ): AnalyticsAuditEvent {
                    $this->assertSame(
                        AuditAction::DATASET_INSPECTED,
                        $action,
                    );

                    return new AnalyticsAuditEvent;
                },
            );

        $this->app->instance(
            AuditRecorder::class,
            $audit,
        );
    }

    public function test_branch_analyst_does_not_receive_identifier_metadata(): void
    {
        $this->actingAs(
            $this->user(
                EmployeeRole::BRANCH_ANALYST,
                branchId: 42,
            ),
        )
            ->get(
                route(
                    'analytics.datasets.show',
                    ['dataset' => 'transactions'],
                ),
            )
            ->assertOk()
            ->assertDontSee('Transaction Reference')
            ->assertDontSee('transaction_reference')
            ->assertSee('Transaction Type')
            ->assertSee('Total Amount')
            ->assertSee(
                'This metadata view includes only fields currently available to your role.',
            );
    }

    public function test_branch_manager_receives_confidential_identifier_metadata(): void
    {
        $this->actingAs(
            $this->user(
                EmployeeRole::BRANCH_MANAGER,
                branchId: 42,
            ),
        )
            ->get(
                route(
                    'analytics.datasets.show',
                    ['dataset' => 'transactions'],
                ),
            )
            ->assertOk()
            ->assertSee('Transaction Reference')
            ->assertSee('transaction_reference');
    }

    public function test_administrator_receives_complete_governance_metadata(): void
    {
        $this->actingAs(
            $this->user(EmployeeRole::ADMINISTRATOR),
        )
            ->get(
                route(
                    'analytics.datasets.show',
                    ['dataset' => 'transactions'],
                ),
            )
            ->assertOk()
            ->assertSee('Transaction Reference')
            ->assertSee(
                'You are viewing the complete semantic metadata catalog',
            )
            ->assertSee('governance')
            ->assertSee('and administration.');
    }

    private function user(
        EmployeeRole $role,
        ?int $branchId = null,
    ): User {
        $user = (new User)->forceFill([
            'id' => 10_000,
            'name' => 'Metadata test user',
            'email' => $role->value.'@aurelia.test',
        ]);

        $user->setRelation(
            'employee',
            (new Employee)->forceFill([
                'id' => 20_000,
                'user_id' => $user->getKey(),
                'branch_id' => $branchId,
                'role' => $role,
                'status' => EmployeeStatus::ACTIVE,
            ]),
        );

        return $user;
    }
}
