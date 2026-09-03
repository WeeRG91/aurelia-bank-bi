<?php

namespace Tests\Feature;

use App\Analytics\Datasets\DatasetKey;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SavedReportExportControllerTest extends TestCase
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
    }

    public function test_another_employee_cannot_export_the_report(): void
    {
        $this->expectNoDatabaseConnection();

        $this->actingAs(
            $this->user(employeeId: 20),
        )->postJson(
            $this->exportUrl(),
            ['format' => 'csv'],
        )->assertForbidden();
    }

    public function test_owner_without_dataset_access_cannot_export(): void
    {
        $this->expectNoDatabaseConnection();

        $this->actingAs(
            $this->user(role: EmployeeRole::ADMINISTRATOR),
        )->postJson(
            $this->exportUrl(),
            ['format' => 'xlsx'],
        )->assertForbidden();
    }

    public function test_suspended_owner_cannot_export(): void
    {
        $this->expectNoDatabaseConnection();

        $this->actingAs(
            $this->user(status: EmployeeStatus::SUSPENDED),
        )->postJson(
            $this->exportUrl(),
            ['format' => 'csv'],
        )->assertForbidden();
    }

    public function test_unsupported_format_is_rejected(): void
    {
        $this->expectNoDatabaseConnection();

        $this->actingAs(
            $this->user(),
        )->postJson(
            $this->exportUrl(),
            ['format' => 'pdf'],
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('format');
    }

    public function test_authorized_csv_export_uses_branch_scope(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(ConnectionInterface::class);

        $database->expects($this->once())
            ->method('connection')
            ->with('pgsql')
            ->willReturn($connection);

        $connection->expects($this->once())
            ->method('statement')
            ->with('SET TRANSACTION READ ONLY')
            ->willReturn(true);

        $connection->expects($this->once())
            ->method('select')
            ->with(
                $this->callback(
                    static fn (string $sql): bool => str_contains(
                        $sql,
                        'WHERE branches.id = ?',
                    ),
                ),
                [42, 100],
            )
            ->willReturn([
                (object) [
                    'transaction_reference' => 'TXN-BRANCH-42',
                ],
            ]);

        $connection->expects($this->once())
            ->method('transaction')
            ->willReturnCallback(
                static function (
                    Closure $callback,
                    int $attempts,
                ) use ($connection): array {
                    return $callback($connection);
                },
            );

        $this->app->instance(
            DatabaseManager::class,
            $database,
        );

        $response = $this->actingAs(
            $this->user(),
        )->postJson(
            $this->exportUrl(),
            ['format' => 'csv'],
        );

        $response
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringContainsString(
            'text/csv',
            (string) $response->headers->get('Content-Type'),
        );

        $this->assertStringContainsString(
            'attachment;',
            (string) $response->headers->get('Content-Disposition'),
        );

        $this->assertStringContainsString(
            '.csv',
            (string) $response->headers->get('Content-Disposition'),
        );

        $this->assertStringStartsWith(
            "\xEF\xBB\xBF",
            $response->getContent(),
        );

        $this->assertStringContainsString(
            'TXN-BRANCH-42',
            $response->getContent(),
        );
    }

    private function exportUrl(): string
    {
        return route(
            'analytics.saved-reports.export',
            ['savedReport' => 500],
        );
    }

    private function user(
        int $employeeId = 10,
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
                'user_id' => $user->id,
                'branch_id' => 42,
                'role' => $role,
                'status' => $status,
            ]),
        );

        return $user;
    }

    private function expectNoDatabaseConnection(): void
    {
        $database = $this->createMock(DatabaseManager::class);

        $database->expects($this->never())
            ->method('connection');

        $this->app->instance(
            DatabaseManager::class,
            $database,
        );
    }
}
