<?php

namespace Tests\Feature;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditRecorder;
use App\Analytics\Auditing\AuditSource;
use App\Analytics\Auditing\AuditSubjectType;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\AnalyticsAuditEvent;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class DashboardWidgetControllerTest extends TestCase
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
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('role', 30);
            $table->string('status', 20);
        });

        Schema::create('saved_reports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_employee_id');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('dataset', 50);
            $table->unsignedSmallInteger('definition_version');
            $table->json('definition');
            $table->timestamps();
            $table->softDeletes();
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
                $table->unsignedBigInteger('saved_report_id');
                $table->string('title', 150);
                $table->string('widget_type', 20);
                $table->unsignedSmallInteger('grid_column');
                $table->unsignedInteger('grid_row');
                $table->unsignedSmallInteger('grid_width');
                $table->unsignedSmallInteger('grid_height');
                $table->json('configuration')->nullable();
                $table->timestamps();
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

    public function test_widget_is_created_with_canonical_configuration(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Treasury overview',
        );

        $report = $this->savedReport(
            ownerEmployeeId: 10,
        );

        $this->ageDashboard($dashboard);

        $response = $this
            ->actingAs($user)
            ->postJson(
                route(
                    'analytics.dashboards.widgets.store',
                    $dashboard,
                ),
                [
                    'saved_report_id' => $report->getKey(),
                    'title' => '  Total transaction value  ',
                    'widget_type' => 'kpi',
                    'grid_column' => 0,
                    'grid_row' => 0,
                    'grid_width' => 4,
                    'grid_height' => 3,
                    'configuration' => null,
                ],
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.dashboardId',
                $dashboard->getKey(),
            )
            ->assertJsonPath(
                'data.savedReportId',
                $report->getKey(),
            )
            ->assertJsonPath(
                'data.title',
                'Total transaction value',
            )
            ->assertJsonPath('data.type', 'kpi')
            ->assertJsonPath('data.layout.column', 0)
            ->assertJsonPath('data.layout.row', 0)
            ->assertJsonPath('data.layout.width', 4)
            ->assertJsonPath('data.layout.height', 3)
            ->assertJsonPath(
                'data.configuration.show_comparison',
                false,
            );

        $widget = DashboardWidget::query()->firstOrFail();

        $this->assertSame(
            ['show_comparison' => false],
            $widget->configuration,
        );

        $this->assertDashboardWasTouched($dashboard);
    }

    public function test_widget_is_updated_and_dashboard_is_touched(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Operations',
        );

        $report = $this->savedReport(
            ownerEmployeeId: 10,
        );

        $widget = $this->widget(
            dashboard: $dashboard,
            report: $report,
        );

        $this->ageDashboard($dashboard);

        $this->actingAs($user)
            ->putJson(
                route(
                    'analytics.dashboards.widgets.update',
                    [
                        'dashboard' => $dashboard,
                        'dashboardWidget' => $widget,
                    ],
                ),
                [
                    'saved_report_id' => $report->getKey(),
                    'title' => '  Transaction table  ',
                    'widget_type' => 'table',
                    'grid_column' => 0,
                    'grid_row' => 4,
                    'grid_width' => 12,
                    'grid_height' => 6,
                    'configuration' => [],
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'data.title',
                'Transaction table',
            )
            ->assertJsonPath('data.type', 'table')
            ->assertJsonPath('data.layout.row', 4)
            ->assertJsonPath('data.layout.width', 12)
            ->assertJsonPath(
                'data.configuration.row_limit',
                25,
            );

        $widget->refresh();

        $this->assertSame(
            ['row_limit' => 25],
            $widget->configuration,
        );

        $this->assertDashboardWasTouched($dashboard);
    }

    public function test_widget_cannot_be_deleted_through_another_dashboard(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $firstDashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'First dashboard',
        );

        $secondDashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Second dashboard',
        );

        $report = $this->savedReport(
            ownerEmployeeId: 10,
        );

        $widget = $this->widget(
            dashboard: $firstDashboard,
            report: $report,
        );

        $this->actingAs($user)
            ->deleteJson(
                route(
                    'analytics.dashboards.widgets.destroy',
                    [
                        'dashboard' => $secondDashboard,
                        'dashboardWidget' => $widget,
                    ],
                ),
            )
            ->assertNotFound();

        $this->assertDatabaseHas('dashboard_widgets', [
            'id' => $widget->getKey(),
            'dashboard_id' => $firstDashboard->getKey(),
        ]);
    }

    public function test_employee_cannot_add_widget_to_another_dashboard(): void
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
        );

        $report = $this->savedReport(
            ownerEmployeeId: 20,
        );

        $this->actingAs($other)
            ->postJson(
                route(
                    'analytics.dashboards.widgets.store',
                    $dashboard,
                ),
                [
                    ...$this->validPayload($report),
                    'title' => 'Unauthorized widget',
                ],
            )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'dashboard_widgets',
            0,
        );

        $this->assertNotSame(
            $owner->getKey(),
            $other->getKey(),
        );
    }

    public function test_widget_is_deleted_and_dashboard_is_touched(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Temporary dashboard',
        );

        $report = $this->savedReport(
            ownerEmployeeId: 10,
        );

        $widget = $this->widget(
            dashboard: $dashboard,
            report: $report,
        );

        $this->ageDashboard($dashboard);

        $this->actingAs($user)
            ->deleteJson(
                route(
                    'analytics.dashboards.widgets.destroy',
                    [
                        'dashboard' => $dashboard,
                        'dashboardWidget' => $widget,
                    ],
                ),
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'dashboard_widgets',
            ['id' => $widget->getKey()],
        );

        $this->assertDashboardWasTouched($dashboard);
    }

    public function test_dashboard_detail_returns_widgets_in_grid_order(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Ordered dashboard',
        );

        $report = $this->savedReport(
            ownerEmployeeId: 10,
        );

        $lateWidget = $this->widget(
            dashboard: $dashboard,
            report: $report,
            title: 'Late row',
            column: 0,
            row: 2,
        );

        $rightWidget = $this->widget(
            dashboard: $dashboard,
            report: $report,
            title: 'Right widget',
            column: 6,
            row: 0,
        );

        $leftWidget = $this->widget(
            dashboard: $dashboard,
            report: $report,
            title: 'Left widget',
            column: 0,
            row: 0,
        );

        $this->actingAs($user)
            ->getJson(
                route(
                    'analytics.dashboards.show',
                    $dashboard,
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $dashboard->getKey(),
            )
            ->assertJsonPath(
                'data.name',
                'Ordered dashboard',
            )
            ->assertJsonPath('data.widgetCount', 3)
            ->assertJsonCount(3, 'data.widgets')
            ->assertJsonPath(
                'data.widgets.0.id',
                $leftWidget->getKey(),
            )
            ->assertJsonPath(
                'data.widgets.1.id',
                $rightWidget->getKey(),
            )
            ->assertJsonPath(
                'data.widgets.2.id',
                $lateWidget->getKey(),
            )
            ->assertJsonPath(
                'data.widgets.0.layout.column',
                0,
            )
            ->assertJsonPath(
                'data.widgets.1.layout.column',
                6,
            )
            ->assertJsonPath(
                'data.widgets.2.layout.row',
                2,
            );
    }

    public function test_employee_cannot_view_another_dashboard(): void
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
        );

        $this->actingAs($other)
            ->getJson(
                route(
                    'analytics.dashboards.show',
                    $dashboard,
                ),
            )
            ->assertForbidden();

        $this->assertNotSame(
            $owner->getKey(),
            $other->getKey(),
        );
    }

    public function test_results_endpoint_hides_unavailable_report_data(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Runtime dashboard',
        );

        $report = $this->savedReport(
            ownerEmployeeId: 10,
        );

        $widget = $this->widget(
            dashboard: $dashboard,
            report: $report,
        );

        $report->delete();

        $this->actingAs($user)
            ->getJson(
                route(
                    'analytics.dashboards.results',
                    $dashboard,
                ),
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.widgetId',
                $widget->getKey(),
            )
            ->assertJsonPath(
                'data.0.type',
                'kpi',
            )
            ->assertJsonPath(
                'data.0.status',
                'unavailable',
            )
            ->assertJsonPath(
                'data.0.dataset',
                null,
            )
            ->assertJsonPath(
                'data.0.dimensions',
                [],
            )
            ->assertJsonPath(
                'data.0.measures',
                [],
            )
            ->assertJsonPath(
                'data.0.rowCount',
                0,
            )
            ->assertJsonPath(
                'data.0.rows',
                [],
            )
            ->assertJsonPath(
                'meta.dashboardId',
                $dashboard->getKey(),
            )
            ->assertJsonPath(
                'meta.reportingTimezone',
                'Europe/Luxembourg',
            )
            ->assertJsonPath(
                'meta.widgetCount',
                1,
            );
    }

    public function test_employee_cannot_execute_another_dashboard(): void
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
            name: 'Private runtime dashboard',
        );

        $this->actingAs($other)
            ->getJson(
                route(
                    'analytics.dashboards.results',
                    $dashboard,
                ),
            )
            ->assertForbidden();

        $this->assertNotSame(
            $owner->getKey(),
            $other->getKey(),
        );
    }

    public function test_widget_creation_is_audited(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Treasury overview',
        );

        $report = $this->savedReport(
            ownerEmployeeId: 10,
        );

        $audit = $this->createMock(
            AuditRecorder::class,
        );

        $audit->expects($this->once())
            ->method('record')
            ->with(
                AuditAction::DASHBOARD_WIDGET_CREATED,
                AuditOutcome::SUCCEEDED,
                AuditSource::WEB,
                $this->callback(
                    static fn (mixed $actor): bool => $actor instanceof Employee
                        && $actor->getKey() === 10,
                ),
                null,
                AuditSubjectType::DASHBOARD_WIDGET,
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
                                'widget_type' => 'kpi',
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
                route(
                    'analytics.dashboards.widgets.store',
                    $dashboard,
                ),
                [
                    'saved_report_id' => $report->getKey(),
                    'title' => 'Transaction value',
                    'widget_type' => 'kpi',
                    'grid_column' => 0,
                    'grid_row' => 0,
                    'grid_width' => 4,
                    'grid_height' => 3,
                    'configuration' => [
                        'show_comparison' => false,
                    ],
                ],
            )
            ->assertCreated();
    }

    public function test_widget_update_is_audited(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Treasury overview',
        );

        $report = $this->savedReport(
            ownerEmployeeId: 10,
        );

        $widget = $this->widget(
            dashboard: $dashboard,
            report: $report,
        );

        $audit = $this->createMock(
            AuditRecorder::class,
        );

        $audit->expects($this->once())
            ->method('record')
            ->with(
                AuditAction::DASHBOARD_WIDGET_UPDATED,
                AuditOutcome::SUCCEEDED,
                AuditSource::WEB,
                $this->callback(
                    static fn (mixed $actor): bool => $actor instanceof Employee
                        && $actor->getKey() === 10,
                ),
                null,
                AuditSubjectType::DASHBOARD_WIDGET,
                $widget->getKey(),
                $this->callback(
                    static function (
                        mixed $context,
                    ): bool {
                        return $context instanceof AuditContext
                            && $context->toArray() === [
                                'widget_type' => 'kpi',
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
                    'analytics.dashboards.widgets.update',
                    [
                        'dashboard' => $dashboard,
                        'dashboardWidget' => $widget,
                    ],
                ),
                [
                    'saved_report_id' => $report->getKey(),
                    'title' => 'Updated transaction value',
                    'widget_type' => 'kpi',
                    'grid_column' => 4,
                    'grid_row' => 0,
                    'grid_width' => 4,
                    'grid_height' => 3,
                    'configuration' => [
                        'show_comparison' => false,
                    ],
                ],
            )
            ->assertOk();
    }

    public function test_widget_deletion_is_audited(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Treasury overview',
        );

        $report = $this->savedReport(
            ownerEmployeeId: 10,
        );

        $widget = $this->widget(
            dashboard: $dashboard,
            report: $report,
        );

        $widgetId = (int) $widget->getKey();

        $audit = $this->createMock(
            AuditRecorder::class,
        );

        $audit->expects($this->once())
            ->method('record')
            ->with(
                AuditAction::DASHBOARD_WIDGET_DELETED,
                AuditOutcome::SUCCEEDED,
                AuditSource::WEB,
                $this->callback(
                    static fn (mixed $actor): bool => $actor instanceof Employee
                        && $actor->getKey() === 10,
                ),
                null,
                AuditSubjectType::DASHBOARD_WIDGET,
                $widgetId,
                $this->callback(
                    static function (
                        mixed $context,
                    ): bool {
                        return $context instanceof AuditContext
                            && $context->toArray() === [
                                'widget_type' => 'kpi',
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
                    'analytics.dashboards.widgets.destroy',
                    [
                        'dashboard' => $dashboard,
                        'dashboardWidget' => $widget,
                    ],
                ),
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'dashboard_widgets',
            [
                'id' => $widgetId,
            ],
        );
    }

    public function test_dashboard_result_execution_is_audited(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Runtime dashboard',
        );

        $report = $this->savedReport(
            ownerEmployeeId: 10,
        );

        $this->widget(
            dashboard: $dashboard,
            report: $report,
        );

        /*
         * This produces an unavailable widget without executing a dataset
         * query. The dashboard execution itself still succeeds.
         */
        $report->delete();

        $audit = $this->createMock(
            AuditRecorder::class,
        );

        $audit->expects($this->once())
            ->method('record')
            ->with(
                AuditAction::DASHBOARD_VIEWED,
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
                    static function (mixed $context): bool {
                        if (! $context instanceof AuditContext) {
                            return false;
                        }

                        $values = $context->toArray();

                        return is_int($values['duration_ms'])
                            && $values['duration_ms'] >= 0
                            && $values['widget_count'] === 1;
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
            ->getJson(
                route(
                    'analytics.dashboards.results',
                    $dashboard,
                ),
            )
            ->assertOk()
            ->assertJsonPath('meta.widgetCount', 1);
    }

    public function test_repeated_dashboard_result_request_uses_cache(): void
    {
        $user = $this->user(
            userId: 100,
            employeeId: 10,
        );

        $dashboard = $this->dashboard(
            ownerEmployeeId: 10,
            name: 'Cached dashboard',
        );

        $report = $this->savedReport(
            ownerEmployeeId: 10,
        );

        $this->widget(
            dashboard: $dashboard,
            report: $report,
        );

        /*
         * An unavailable widget avoids executing a dataset query while
         * still producing a valid, cacheable dashboard response.
         */
        $report->delete();

        $url = route(
            'analytics.dashboards.results',
            $dashboard,
        );

        $first = $this
            ->actingAs($user)
            ->getJson($url)
            ->assertOk()
            ->assertJsonPath('meta.cacheHit', false);

        $second = $this
            ->actingAs($user)
            ->getJson($url)
            ->assertOk()
            ->assertJsonPath('meta.cacheHit', true);

        $this->assertSame(
            $first->json('data'),
            $second->json('data'),
        );

        $this->assertSame(
            $first->json('meta.generatedAt'),
            $second->json('meta.generatedAt'),
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
            'branch_id' => $employeeId,
            'role' => EmployeeRole::BRANCH_ANALYST->value,
            'status' => EmployeeStatus::ACTIVE->value,
        ]);

        return User::query()->findOrFail($userId);
    }

    private function dashboard(
        int $ownerEmployeeId,
        string $name,
    ): Dashboard {
        return Dashboard::query()->create([
            'owner_employee_id' => $ownerEmployeeId,
            'name' => $name,
            'description' => null,
            'is_default' => false,
        ]);
    }

    private function savedReport(
        int $ownerEmployeeId,
    ): SavedReport {
        return SavedReport::query()->create([
            'owner_employee_id' => $ownerEmployeeId,
            'name' => 'Transaction total',
            'description' => null,
            'dataset' => 'transactions',
            'definition_version' => 1,
            'definition' => [
                'dimensions' => ['currency'],
                'measures' => ['total_amount'],
                'filters' => [],
                'relative_date' => null,
                'limit' => 100,
                'visualization' => null,
            ],
        ]);
    }

    private function widget(
        Dashboard $dashboard,
        SavedReport $report,
        string $title = 'Transaction total',
        int $column = 0,
        int $row = 0,
    ): DashboardWidget {
        return DashboardWidget::query()->create([
            'dashboard_id' => $dashboard->getKey(),
            'saved_report_id' => $report->getKey(),
            'title' => $title,
            'widget_type' => 'kpi',
            'grid_column' => $column,
            'grid_row' => $row,
            'grid_width' => 4,
            'grid_height' => 3,
            'configuration' => [
                'show_comparison' => false,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(
        SavedReport $report,
    ): array {
        return [
            'saved_report_id' => $report->getKey(),
            'title' => 'Transaction total',
            'widget_type' => 'kpi',
            'grid_column' => 0,
            'grid_row' => 0,
            'grid_width' => 4,
            'grid_height' => 3,
            'configuration' => [
                'show_comparison' => false,
            ],
        ];
    }

    private function ageDashboard(
        Dashboard $dashboard,
    ): void {
        DB::table('dashboards')
            ->where('id', $dashboard->getKey())
            ->update([
                'updated_at' => '2026-01-01 00:00:00',
            ]);
    }

    private function assertDashboardWasTouched(
        Dashboard $dashboard,
    ): void {
        $dashboard->refresh();

        $this->assertTrue(
            $dashboard->updated_at->greaterThan(
                '2026-01-01 00:00:00',
            ),
        );
    }
}
