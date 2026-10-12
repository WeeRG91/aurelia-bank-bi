<?php

namespace Tests\Unit;

use App\Analytics\Time\ReportingTimezone;
use App\Analytics\Warehouse\Aggregates\DailyTransactionAggregateQuery;
use App\Analytics\Warehouse\Aggregates\DatabaseDailyTransactionAggregateRefresher;
use Closure;
use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;

final class DatabaseDailyTransactionAggregateRefresherTest extends TestCase
{
    public function test_timezone_aggregate_is_rebuilt_transactionally(): void
    {
        $database = $this->createMock(
            DatabaseManager::class,
        );

        $connection = $this->createMock(
            ConnectionInterface::class,
        );

        $database->expects($this->once())
            ->method('connection')
            ->with('warehouse')
            ->willReturn($connection);

        $connection->expects($this->once())
            ->method('select')
            ->with(
                $this->stringContains(
                    'pg_advisory_xact_lock',
                ),
                [
                    'aurelia:agg_daily_transaction_activity:'
                    .'Europe/Luxembourg',
                ],
            )
            ->willReturn([]);

        $connection->expects($this->once())
            ->method('delete')
            ->with(
                $this->stringContains(
                    'DELETE FROM warehouse.'
                    .'agg_daily_transaction_activity',
                ),
                ['Europe/Luxembourg'],
            )
            ->willReturn(12);

        $connection->expects($this->once())
            ->method('affectingStatement')
            ->with(
                $this->callback(
                    static fn (string $sql): bool => str_contains(
                        $sql,
                        'INSERT INTO warehouse.'
                        .'agg_daily_transaction_activity',
                    ) && str_contains(
                        $sql,
                        'FROM fact_transactions',
                    ),
                ),
                [
                    '2026-10-12 15:30:00+00:00',
                    'Europe/Luxembourg',
                    'Europe/Luxembourg',
                ],
            )
            ->willReturn(27);

        $connection->expects($this->once())
            ->method('transaction')
            ->with(
                $this->isInstanceOf(Closure::class),
                1,
            )
            ->willReturnCallback(
                static function (
                    Closure $callback,
                    int $attempts,
                ) use ($connection) {
                    return $callback($connection);
                },
            );

        $result = (new DatabaseDailyTransactionAggregateRefresher(
            $database,
            new DailyTransactionAggregateQuery,
        ))->refresh(
            new ReportingTimezone(
                'Europe/Luxembourg',
            ),
            new DateTimeImmutable(
                '2026-10-12T17:30:00+02:00',
            ),
        );

        $this->assertSame(
            'Europe/Luxembourg',
            $result->reportingTimezone->name,
        );

        $this->assertSame(27, $result->rowCount);

        $this->assertSame(
            '2026-10-12T15:30:00+00:00',
            $result->refreshedAt->format(DATE_ATOM),
        );
    }
}
