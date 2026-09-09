<?php

namespace Tests\Feature;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditRecorder;
use App\Analytics\Auditing\AuditSource;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Datasets\DatasetKey;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\AnalyticsAuditEvent;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ReportBuilderAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $report = (new SavedReport)->forceFill([
            'id' => 500,
            'owner_employee_id' => 10,
            'name' => 'Monthly transactions',
            'dataset' => DatasetKey::TRANSACTIONS,
            'definition_version' => 1,
            'definition' => [
                'dimensions' => ['transaction_type'],
                'measures' => [],
                'filters' => [],
                'relative_date' => null,
                'limit' => 100,
                'visualization' => null,
            ],
        ]);

        Route::bind(
            'savedReport',
            static fn (string $value): SavedReport => $report,
        );
    }

    public function test_opening_an_accessible_report_is_audited(): void
    {
        $audit = $this->createMock(AuditRecorder::class);

        $audit->expects($this->once())
            ->method('record')
            ->with(
                AuditAction::REPORT_OPENED,
                AuditOutcome::SUCCEEDED,
                AuditSource::WEB,
                $this->isInstanceOf(Employee::class),
                DatasetKey::TRANSACTIONS,
                AuditSubjectType::SAVED_REPORT,
                500,
                $this->callback(
                    static function (AuditContext $context): bool {
                        return $context->toArray() === [
                            'definition_version' => 1,
                            'dimension_count' => 1,
                            'filter_count' => 0,
                            'limit' => 100,
                            'measure_count' => 0,
                        ];
                    },
                ),
                $this->anything(),
                '127.0.0.1',
                $this->anything(),
            )
            ->willReturn(new AnalyticsAuditEvent);

        $this->app->instance(AuditRecorder::class, $audit);

        $this->actingAs($this->user(employeeId: 10))
            ->get($this->reportUrl())
            ->assertOk();
    }

    public function test_denied_report_open_is_audited(): void
    {
        $audit = $this->createMock(AuditRecorder::class);

        $audit->expects($this->once())
            ->method('record')
            ->with(
                AuditAction::REPORT_OPENED,
                AuditOutcome::DENIED,
                AuditSource::WEB,
                $this->isInstanceOf(Employee::class),
                DatasetKey::TRANSACTIONS,
                AuditSubjectType::SAVED_REPORT,
                500,
                $this->callback(
                    static fn (AuditContext $context): bool => $context
                        ->toArray() === [
                            'reason_code' => 'report_authorization_denied',
                        ],
                ),
                $this->anything(),
                '127.0.0.1',
                $this->anything(),
            )
            ->willReturn(new AnalyticsAuditEvent);

        $this->app->instance(AuditRecorder::class, $audit);

        $this->actingAs($this->user(employeeId: 20))
            ->get($this->reportUrl())
            ->assertForbidden();
    }

    public function test_report_with_forbidden_fields_records_denied_audit(): void
    {
        $report = (new SavedReport)->forceFill([
            'id' => 500,
            'owner_employee_id' => 10,
            'name' => 'Restricted transaction report',
            'dataset' => DatasetKey::TRANSACTIONS,
            'definition_version' => 1,
            'definition' => [
                'dimensions' => ['transaction_reference'],
                'measures' => [],
                'filters' => [],
                'relative_date' => null,
                'limit' => 100,
                'visualization' => null,
            ],
        ]);

        Route::bind(
            'savedReport',
            static fn (string $value): SavedReport => $report,
        );

        $audit = $this->createMock(AuditRecorder::class);

        $audit->expects($this->once())
            ->method('record')
            ->with(
                AuditAction::REPORT_OPENED,
                AuditOutcome::DENIED,
                AuditSource::WEB,
                $this->isInstanceOf(Employee::class),
                DatasetKey::TRANSACTIONS,
                AuditSubjectType::SAVED_REPORT,
                500,
                $this->callback(
                    static fn (AuditContext $context): bool => $context
                        ->toArray() === [
                            'reason_code' => 'field_access_denied',
                        ],
                ),
                $this->anything(),
                '127.0.0.1',
                $this->anything(),
            )
            ->willReturn(new AnalyticsAuditEvent);

        $this->app->instance(AuditRecorder::class, $audit);

        $this->actingAs($this->user(employeeId: 10))
            ->get($this->reportUrl())
            ->assertRedirect(
                route('analytics.saved-reports.index'),
            )
            ->assertSessionHas(
                'error',
                'This report contains fields that are no longer available to your role. Create a new report using the currently permitted fields.',
            );
    }

    public function test_owner_without_dataset_access_records_denied_audit(): void
    {
        $audit = $this->createMock(AuditRecorder::class);

        $audit->expects($this->once())
            ->method('record')
            ->with(
                AuditAction::REPORT_OPENED,
                AuditOutcome::DENIED,
                AuditSource::WEB,
                $this->isInstanceOf(Employee::class),
                DatasetKey::TRANSACTIONS,
                AuditSubjectType::SAVED_REPORT,
                500,
                $this->callback(
                    static fn (AuditContext $context): bool => $context
                        ->toArray() === [
                            'reason_code' => 'dataset_access_denied',
                        ],
                ),
                $this->anything(),
                '127.0.0.1',
                $this->anything(),
            )
            ->willReturn(new AnalyticsAuditEvent);

        $this->app->instance(AuditRecorder::class, $audit);

        $this->actingAs(
            $this->user(
                employeeId: 10,
                role: EmployeeRole::ADMINISTRATOR,
            ),
        )
            ->get($this->reportUrl())
            ->assertForbidden();
    }

    private function reportUrl(): string
    {
        return route(
            'analytics.report-builder',
            ['savedReport' => 500],
        );
    }

    private function user(
        int $employeeId,
        EmployeeRole $role = EmployeeRole::BRANCH_ANALYST,
    ): User {
        $user = (new User)->forceFill([
            'id' => $employeeId + 1_000,
        ]);

        $user->setRelation(
            'employee',
            (new Employee)->forceFill([
                'id' => $employeeId,
                'user_id' => $user->getKey(),
                'branch_id' => 42,
                'role' => $role,
                'status' => EmployeeStatus::ACTIVE,
            ]),
        );

        return $user;
    }
}
