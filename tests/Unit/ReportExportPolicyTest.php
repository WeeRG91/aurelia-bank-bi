<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetFieldAccess;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Exports\ExportFormat;
use App\Analytics\Exports\ReportExportStatus;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\ReportExport;
use App\Models\User;
use App\Policies\ReportExportPolicy;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class ReportExportPolicyTest extends TestCase
{
    public function test_owner_can_download_completed_unexpired_export(): void
    {
        $policy = $this->policy();
        $owner = $this->user(employeeId: 10);

        $this->assertTrue(
            $policy->download(
                $owner,
                $this->export(
                    ownerEmployeeId: 10,
                    status: ReportExportStatus::COMPLETED,
                ),
            ),
        );
    }

    public function test_another_employee_cannot_download_export(): void
    {
        $this->assertFalse(
            $this->policy()->download(
                $this->user(employeeId: 20),
                $this->export(ownerEmployeeId: 10),
            ),
        );
    }

    public function test_incomplete_or_expired_export_cannot_be_downloaded(): void
    {
        $policy = $this->policy();
        $owner = $this->user(employeeId: 10);

        foreach ([
            ReportExportStatus::QUEUED,
            ReportExportStatus::PROCESSING,
            ReportExportStatus::FAILED,
            ReportExportStatus::EXPIRED,
        ] as $status) {
            $this->assertFalse(
                $policy->download(
                    $owner,
                    $this->export(
                        ownerEmployeeId: 10,
                        status: $status,
                    ),
                ),
            );
        }

        $this->assertFalse(
            $policy->download(
                $owner,
                $this->export(
                    ownerEmployeeId: 10,
                    expiresAt: CarbonImmutable::now()->subMinute(),
                ),
            ),
        );
    }

    public function test_download_is_denied_after_dataset_access_is_lost(): void
    {
        $administrator = $this->user(
            employeeId: 10,
            role: EmployeeRole::ADMINISTRATOR,
        );

        $this->assertFalse(
            $this->policy()->download(
                $administrator,
                $this->export(ownerEmployeeId: 10),
            ),
        );
    }

    public function test_inactive_employee_is_rejected_by_policy_guard(): void
    {
        $user = $this->user(
            employeeId: 10,
            status: EmployeeStatus::SUSPENDED,
        );

        $this->assertFalse(
            $this->policy()->before($user),
        );
    }

    public function test_download_is_denied_after_field_access_is_lost(): void
    {
        $owner = $this->user(employeeId: 10);
        $export = $this->export(ownerEmployeeId: 10);

        $export->forceFill([
            'definition' => [
                'dimensions' => [
                    'transaction_reference',
                ],
                'measures' => [],
                'filters' => [],
                'relative_date' => null,
                'limit' => 100,
                'visualization' => null,
            ],
        ]);

        $this->assertFalse(
            $this->policy()->download($owner, $export),
        );
    }

    private function policy(): ReportExportPolicy
    {
        $registry = new DatasetRegistry;
        $datasetAccess = new DatasetAccess($registry);

        return new ReportExportPolicy(
            $datasetAccess,
            new DatasetFieldAccess(
                $registry,
                $datasetAccess,
            ),
        );
    }

    private function export(
        int $ownerEmployeeId,
        ReportExportStatus $status = ReportExportStatus::COMPLETED,
        ?CarbonImmutable $expiresAt = null,
    ): ReportExport {
        return (new ReportExport)->forceFill([
            'requested_by_employee_id' => $ownerEmployeeId,
            'dataset' => DatasetKey::TRANSACTIONS,
            'definition' => [
                'dimensions' => [
                    'transaction_type',
                    'currency',
                ],
                'measures' => [
                    'total_amount',
                ],
                'filters' => [],
                'relative_date' => null,
                'limit' => 100,
                'visualization' => null,
            ],
            'format' => ExportFormat::CSV,
            'status' => $status,
            'disk' => 'report_exports',
            'path' => 'example/report.csv',
            'filename' => 'report.csv',
            'expires_at' => $expiresAt
                ?? CarbonImmutable::now()->addHour(),
        ]);
    }

    private function user(
        int $employeeId,
        EmployeeRole $role = EmployeeRole::BRANCH_ANALYST,
        EmployeeStatus $status = EmployeeStatus::ACTIVE,
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
                'status' => $status,
            ]),
        );

        return $user;
    }
}
