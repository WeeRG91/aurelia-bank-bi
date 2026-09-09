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
use App\Models\User;
use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Tests\TestCase;

class ReportPreviewControllerTest extends TestCase
{
    public function test_authorized_preview_returns_stable_json(): void
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
                    ) && str_contains(
                        $sql,
                        'transactions.currency = ?',
                    ),
                ),
                [42, 'EUR', 100],
            )
            ->willReturn([
                (object) [
                    'transaction_type' => 'transfer',
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

        $audit = $this->createMock(AuditRecorder::class);

        $audit->expects($this->once())
            ->method('record')
            ->with(
                AuditAction::REPORT_PREVIEWED,
                AuditOutcome::SUCCEEDED,
                AuditSource::WEB,
                $this->isInstanceOf(Employee::class),
                DatasetKey::TRANSACTIONS,
                AuditSubjectType::DATASET,
                DatasetKey::TRANSACTIONS->value,
                $this->callback(
                    static function (AuditContext $context): bool {
                        $values = $context->toArray();

                        return $values['dimension_count'] === 1
                            && is_int($values['duration_ms'])
                            && $values['duration_ms'] >= 0
                            && $values['filter_count'] === 1
                            && $values['limit'] === 100
                            && $values['measure_count'] === 0
                            && $values['row_count'] === 1;
                    },
                ),
                $this->callback(
                    static fn (?string $requestId): bool => is_string(
                        $requestId,
                    ) && preg_match(
                        '/^[0-9a-f-]{36}$/',
                        $requestId,
                    ) === 1,
                ),
                '127.0.0.1',
                $this->anything(),
            )
            ->willReturn(new AnalyticsAuditEvent);

        $this->app->instance(
            AuditRecorder::class,
            $audit,
        );

        $response = $this
            ->actingAs(
                $this->user(
                    EmployeeRole::BRANCH_ANALYST,
                    branchId: 42,
                ),
            )
            ->postJson(
                route('analytics.report-preview'),
                [
                    'dataset' => 'transactions',
                    'dimensions' => ['transaction_type'],
                    'measures' => [],
                    'filters' => [
                        [
                            'dimension' => 'currency',
                            'operator' => 'equals',
                            'value' => 'EUR',
                        ],
                    ],
                    'limit' => 100,
                ],
            );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    [
                        'transaction_type' => 'transfer',
                    ],
                ],
                'meta' => [
                    'dataset' => 'transactions',
                    'dimensions' => ['transaction_type'],
                    'measures' => [],
                    'rowCount' => 1,
                    'limit' => 100,
                    'reportingTimezone' => 'Europe/Luxembourg',
                ],
            ]);
    }

    public function test_administrator_cannot_preview_business_data(): void
    {
        $database = $this->createMock(DatabaseManager::class);

        $database->expects($this->never())
            ->method('connection');

        $this->app->instance(
            DatabaseManager::class,
            $database,
        );

        $this
            ->actingAs(
                $this->user(EmployeeRole::ADMINISTRATOR),
            )
            ->postJson(
                route('analytics.report-preview'),
                [
                    'dataset' => 'transactions',
                    'dimensions' => ['transaction_type'],
                    'measures' => [],
                ],
            )
            ->assertForbidden();
    }

    private function user(
        EmployeeRole $role,
        ?int $branchId = null,
    ): User {
        $user = (new User)->forceFill([
            'id' => 20,
            'user_id' => 10,
        ]);

        $user->setRelation(
            'employee',
            (new Employee)->forceFill([
                'user_id' => 10,
                'branch_id' => $branchId,
                'role' => $role,
                'status' => EmployeeStatus::ACTIVE,
            ]),
        );

        return $user;
    }
}
