<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardDataProvider;
use App\Analytics\Dashboards\DashboardWidgetKey;
use App\Analytics\Time\RelativeDatePreset;
use App\Analytics\Time\ReportingTimezone;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use Closure;
use DateInvalidTimeZoneException;
use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Tests\TestCase;
use Throwable;

final class DashboardDataProviderTest extends TestCase
{
    /**
     * @throws DateInvalidTimeZoneException
     * @throws Throwable
     */
    public function test_authorized_user_receives_governed_widget_results(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(ConnectionInterface::class);

        $database->expects($this->exactly(3))
            ->method('connection')
            ->with('pgsql')
            ->willReturn($connection);

        $connection->expects($this->exactly(3))
            ->method('statement')
            ->with('SET TRANSACTION READ ONLY')
            ->willReturn(true);

        $connection->expects($this->exactly(3))
            ->method('select')
            ->with(
                $this->callback(
                    static fn (string $sql): bool => str_contains(
                        $sql,
                        'WHERE branches.id = ?',
                    ),
                ),
                $this->callback(
                    static fn (array $bindings): bool => in_array(
                        42,
                        $bindings,
                        true,
                    ),
                ),
            )
            ->willReturn([
                (object) [
                    'transaction_count' => 5,
                ],
            ]);

        $connection->expects($this->exactly(3))
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

        $results = app(DashboardDataProvider::class)->forUser(
            user: $this->user(EmployeeRole::BRANCH_ANALYST),
            now: new DateTimeImmutable(
                '2026-09-09T12:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $this->assertSame(
            DashboardWidgetKey::cases(),
            array_map(
                static fn ($result): DashboardWidgetKey => $result
                    ->widget
                    ->key,
                $results,
            ),
        );

        foreach ($results as $result) {
            $this->assertSame(
                RelativeDatePreset::LAST_30_DAYS,
                $result->period,
            );

            $this->assertSame(
                [['transaction_count' => 5]],
                $result->rows,
            );
        }
    }

    /**
     * @throws DateInvalidTimeZoneException
     * @throws Throwable
     */
    public function test_user_without_dataset_access_executes_no_queries(): void
    {
        $database = $this->createMock(DatabaseManager::class);

        $database->expects($this->never())
            ->method('connection');

        $this->app->instance(
            DatabaseManager::class,
            $database,
        );

        $results = app(DashboardDataProvider::class)->forUser(
            user: $this->user(EmployeeRole::ADMINISTRATOR),
            now: new DateTimeImmutable(
                '2026-09-09T12:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $this->assertSame([], $results);
    }

    private function user(EmployeeRole $role): User
    {
        $user = (new User)->forceFill([
            'id' => 1_000,
        ]);

        $user->setRelation(
            'employee',
            (new Employee)->forceFill([
                'id' => 10,
                'user_id' => $user->getKey(),
                'branch_id' => 42,
                'role' => $role,
                'status' => EmployeeStatus::ACTIVE,
            ]),
        );

        return $user;
    }
}
