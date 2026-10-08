<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardDataProvider;
use App\Analytics\Dashboards\DashboardPeriod;
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
    public function test_repeated_request_uses_cached_governed_widget_results(): void
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

        $provider = app(DashboardDataProvider::class);

        $user = $this->user(
            EmployeeRole::BRANCH_ANALYST,
        );

        $now = new DateTimeImmutable(
            '2026-09-09T12:00:00+02:00',
        );

        $reportingTimezone = new ReportingTimezone(
            'Europe/Luxembourg',
        );

        $firstEntry = $provider->forUser(
            user: $user,
            now: $now,
            reportingTimezone: $reportingTimezone,
            period: DashboardPeriod::LAST_30_DAYS,
        );

        $secondEntry = $provider->forUser(
            user: $user,
            now: $now,
            reportingTimezone: $reportingTimezone,
            period: DashboardPeriod::LAST_30_DAYS,
        );

        $this->assertFalse($firstEntry->cacheHit);
        $this->assertTrue($secondEntry->cacheHit);

        $this->assertSame(
            $firstEntry->generatedAt->format(DATE_ATOM),
            $secondEntry->generatedAt->format(DATE_ATOM),
        );

        $this->assertEquals(
            $firstEntry->widgets,
            $secondEntry->widgets,
        );

        $results = $secondEntry->widgets;

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

        $entry = app(DashboardDataProvider::class)->forUser(
            user: $this->user(EmployeeRole::ADMINISTRATOR),
            now: new DateTimeImmutable(
                '2026-09-09T12:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
            period: DashboardPeriod::LAST_30_DAYS,
        );

        $this->assertSame([], $entry->widgets);
        $this->assertFalse($entry->cacheHit);
    }

    /**
     * @throws DateInvalidTimeZoneException
     * @throws Throwable
     */
    public function test_branch_scoped_cache_entries_are_never_shared(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(ConnectionInterface::class);

        // Three widgets for each of two different branches.
        $database->expects($this->exactly(6))
            ->method('connection')
            ->with('pgsql')
            ->willReturn($connection);

        $connection->expects($this->exactly(6))
            ->method('statement')
            ->with('SET TRANSACTION READ ONLY')
            ->willReturn(true);

        $connection->expects($this->exactly(6))
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
                    ) || in_array(
                        84,
                        $bindings,
                        true,
                    ),
                ),
            )
            ->willReturnCallback(
                static function (
                    string $sql,
                    array $bindings,
                ): array {
                    $branchId = in_array(
                        42,
                        $bindings,
                        true,
                    ) ? 42 : 84;

                    return [
                        (object) [
                            'transaction_count' => $branchId,
                        ],
                    ];
                },
            );

        $connection->expects($this->exactly(6))
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

        $provider = app(DashboardDataProvider::class);

        $now = new DateTimeImmutable(
            '2026-10-08T12:00:00+02:00',
        );

        $reportingTimezone = new ReportingTimezone(
            'Europe/Luxembourg',
        );

        $branch42 = $provider->forUser(
            user: $this->user(
                EmployeeRole::BRANCH_ANALYST,
                branchId: 42,
                userId: 1_000,
            ),
            now: $now,
            reportingTimezone: $reportingTimezone,
            period: DashboardPeriod::LAST_30_DAYS,
        );

        $branch84 = $provider->forUser(
            user: $this->user(
                EmployeeRole::BRANCH_ANALYST,
                branchId: 84,
                userId: 2_000,
            ),
            now: $now,
            reportingTimezone: $reportingTimezone,
            period: DashboardPeriod::LAST_30_DAYS,
        );

        $branch42Cached = $provider->forUser(
            user: $this->user(
                EmployeeRole::BRANCH_ANALYST,
                branchId: 42,
                userId: 3_000,
            ),
            now: $now,
            reportingTimezone: $reportingTimezone,
            period: DashboardPeriod::LAST_30_DAYS,
        );

        $this->assertFalse($branch42->cacheHit);
        $this->assertFalse($branch84->cacheHit);
        $this->assertTrue($branch42Cached->cacheHit);

        $this->assertSame(
            42,
            $branch42Cached->widgets[0]->rows[0]['transaction_count'],
        );

        $this->assertSame(
            84,
            $branch84->widgets[0]->rows[0]['transaction_count'],
        );
    }

    private function user(
        EmployeeRole $role,
        int $branchId = 42,
        int $userId = 1_000,
    ): User {
        $user = (new User)->forceFill([
            'id' => $userId,
        ]);

        $user->setRelation(
            'employee',
            (new Employee)->forceFill([
                'id' => $userId + 10,
                'user_id' => $user->getKey(),
                'branch_id' => $branchId,
                'role' => $role,
                'status' => EmployeeStatus::ACTIVE,
            ]),
        );

        return $user;
    }
}
