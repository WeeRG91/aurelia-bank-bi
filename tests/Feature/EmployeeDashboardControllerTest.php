<?php

namespace Tests\Feature;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditRecorder;
use App\Analytics\Auditing\AuditSource;
use App\Analytics\Auditing\AuditSubjectType;
use App\Enums\EmployeeStatus;
use App\Models\AnalyticsAuditEvent;
use App\Models\Dashboard;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class EmployeeDashboardControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status', 20);
        });

        Schema::create('dashboards', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_employee_id');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create(
            'dashboard_widgets',
            function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('dashboard_id');
            },
        );

        $audit = $this->createStub(
            AuditRecorder::class,
        );

        $audit->method('record')
            ->willReturn(new AnalyticsAuditEvent);

        $this->app->instance(
            AuditRecorder::class,
            $audit,
        );
    }

    public function test_employee_only_receives_owned_dashboards(): void
    {
        $owner = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $other = $this->user(
            userId: 200,
            employeeId: 20,
        );

        $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Owned dashboard',
            isDefault: true,
        );

        $this->dashboard(
            ownerEmployeeId: 20,
            name: 'Other dashboard',
            isDefault: true,
        );

        $this->actingAs($owner)
            ->getJson(route('analytics.dashboards.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.name',
                'Owned dashboard',
            )
            ->assertJsonPath(
                'data.0.isDefault',
                true,
            )
            ->assertJsonPath(
                'data.0.widgetCount',
                0,
            );

        $this->assertNotSame(
            $owner->getKey(),
            $other->getKey(),
        );
    }

    public function test_dashboard_is_created_with_canonical_metadata(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('analytics.dashboards.store'),
                [
                    'name' => '  Treasury overview  ',
                    'description' => '  Daily liquidity  ',
                    'is_default' => true,
                ],
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.name',
                'Treasury overview',
            )
            ->assertJsonPath(
                'data.description',
                'Daily liquidity',
            )
            ->assertJsonPath(
                'data.isDefault',
                true,
            )
            ->assertJsonPath(
                'data.widgetCount',
                0,
            );

        $this->assertDatabaseHas('dashboards', [
            'owner_employee_id' => 10,
            'name' => 'Treasury overview',
            'description' => 'Daily liquidity',
            'is_default' => true,
        ]);
    }

    public function test_updating_dashboard_transfers_default_status(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $previousDefault = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Previous default',
            isDefault: true,
        );

        $replacement = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Replacement',
            isDefault: false,
        );

        $this->actingAs($user)
            ->putJson(
                route(
                    'analytics.dashboards.update',
                    $replacement,
                ),
                [
                    'name' => 'New default',
                    'description' => null,
                    'is_default' => true,
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'data.name',
                'New default',
            )
            ->assertJsonPath(
                'data.isDefault',
                true,
            );

        $this->assertFalse(
            $previousDefault->fresh()->is_default,
        );

        $this->assertTrue(
            $replacement->fresh()->is_default,
        );
    }

    public function test_employee_cannot_update_another_dashboard(): void
    {
        $owner = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $other = $this->user(
            userId: 200,
            employeeId: 20,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Private dashboard',
            isDefault: false,
        );

        $this->actingAs($other)
            ->putJson(
                route(
                    'analytics.dashboards.update',
                    $dashboard,
                ),
                [
                    'name' => 'Unauthorized change',
                    'description' => null,
                    'is_default' => false,
                ],
            )
            ->assertForbidden();

        $this->assertSame(
            'Private dashboard',
            $dashboard->fresh()->name,
        );

        $this->assertNotSame(
            $owner->getKey(),
            $other->getKey(),
        );
    }

    public function test_dashboard_is_soft_deleted(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Temporary dashboard',
            isDefault: false,
        );

        $this->actingAs($user)
            ->deleteJson(
                route(
                    'analytics.dashboards.destroy',
                    $dashboard,
                ),
            )
            ->assertNoContent();

        $this->assertSoftDeleted('dashboards', [
            'id' => $dashboard->getKey(),
        ]);
    }

    public function test_dashboard_creation_is_audited(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $audit = $this->createMock(
            AuditRecorder::class,
        );

        $audit->expects($this->once())
            ->method('record')
            ->with(
                AuditAction::DASHBOARD_CREATED,
                AuditOutcome::SUCCEEDED,
                AuditSource::WEB,
                $this->callback(
                    static fn (mixed $actor): bool => $actor instanceof Employee
                        && $actor->getKey() === 10,
                ),
                null,
                AuditSubjectType::DASHBOARD,
                $this->callback(
                    static fn (
                        int|string|null $subjectId,
                    ): bool => is_int($subjectId)
                        && $subjectId > 0,
                ),
                $this->callback(
                    static function (
                        mixed $context,
                    ): bool {
                        return $context instanceof AuditContext
                            && $context->toArray() === [
                                'widget_count' => 0,
                            ];
                    },
                ),
                $this->anything(),
                '127.0.0.1',
                $this->anything(),
            )
            ->willReturn(new AnalyticsAuditEvent);

        $this->app->instance(
            AuditRecorder::class,
            $audit,
        );

        $this
            ->actingAs($user)
            ->postJson(
                route('analytics.dashboards.store'),
                [
                    'name' => 'Audited dashboard',
                    'description' => null,
                    'is_default' => false,
                ],
            )
            ->assertCreated();
    }

    public function test_dashboard_update_is_audited(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Original dashboard',
            isDefault: false,
        );

        $audit = $this->createMock(
            AuditRecorder::class,
        );

        $audit->expects($this->once())
            ->method('record')
            ->with(
                AuditAction::DASHBOARD_UPDATED,
                AuditOutcome::SUCCEEDED,
                AuditSource::WEB,
                $this->callback(
                    static fn (mixed $actor): bool => $actor instanceof Employee
                        && $actor->getKey() === 10,
                ),
                null,
                AuditSubjectType::DASHBOARD,
                $dashboard->getKey(),
                $this->callback(
                    static function (
                        mixed $context,
                    ): bool {
                        return $context instanceof AuditContext
                            && $context->toArray() === [
                                'widget_count' => 0,
                            ];
                    },
                ),
                $this->anything(),
                '127.0.0.1',
                $this->anything(),
            )
            ->willReturn(new AnalyticsAuditEvent);

        $this->app->instance(
            AuditRecorder::class,
            $audit,
        );

        $this
            ->actingAs($user)
            ->putJson(
                route(
                    'analytics.dashboards.update',
                    $dashboard,
                ),
                [
                    'name' => 'Updated dashboard',
                    'description' => 'Updated description',
                    'is_default' => true,
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'data.name',
                'Updated dashboard',
            );
    }

    public function test_dashboard_deletion_is_audited(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Delete me',
            isDefault: false,
        );

        $audit = $this->createMock(
            AuditRecorder::class,
        );

        $audit->expects($this->once())
            ->method('record')
            ->with(
                AuditAction::DASHBOARD_DELETED,
                AuditOutcome::SUCCEEDED,
                AuditSource::WEB,
                $this->callback(
                    static fn (mixed $actor): bool => $actor instanceof Employee
                        && $actor->getKey() === 10,
                ),
                null,
                AuditSubjectType::DASHBOARD,
                $dashboard->getKey(),
                $this->callback(
                    static function (
                        mixed $context,
                    ): bool {
                        return $context instanceof AuditContext
                            && $context->toArray() === [
                                'widget_count' => 0,
                            ];
                    },
                ),
                $this->anything(),
                '127.0.0.1',
                $this->anything(),
            )
            ->willReturn(new AnalyticsAuditEvent);

        $this->app->instance(
            AuditRecorder::class,
            $audit,
        );

        $this
            ->actingAs($user)
            ->deleteJson(
                route(
                    'analytics.dashboards.destroy',
                    $dashboard,
                ),
            )
            ->assertNoContent();

        $this->assertSoftDeleted(
            'dashboards',
            [
                'id' => $dashboard->getKey(),
            ],
        );
    }

    private function user(
        int $userId,
        int $employeeId,
    ): User {
        DB::table('users')->insert([
            'id' => $userId,
            'name' => "Employee {$employeeId}",
            'email' => "employee{$employeeId}@aurelia.test",
            'password' => 'not-used-in-this-test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employees')->insert([
            'id' => $employeeId,
            'user_id' => $userId,
            'status' => EmployeeStatus::ACTIVE->value,
        ]);

        return User::query()->findOrFail($userId);
    }

    private function dashboard(
        int $ownerEmployeeId,
        string $name,
        bool $isDefault,
    ): Dashboard {
        return Dashboard::query()->create([
            'owner_employee_id' => $ownerEmployeeId,
            'name' => $name,
            'description' => null,
            'is_default' => $isDefault,
        ]);
    }
}
