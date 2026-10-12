<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Dimensions\DatabaseDateDimensionWriter;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use PHPUnit\Framework\TestCase;

final class DatabaseDateDimensionWriterTest extends TestCase
{
    public function test_callback_executes_inside_warehouse_transaction(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);

        $database->expects($this->once())
            ->method('connection')
            ->with('warehouse')
            ->willReturn($connection);

        $connection->expects($this->once())
            ->method('transaction')
            ->willReturnCallback(
                static fn (Closure $callback): mixed => $callback(),
            );

        $writer = new DatabaseDateDimensionWriter($database);

        $this->assertSame(
            'committed',
            $writer->transaction(
                static fn (): string => 'committed',
            ),
        );
    }

    public function test_rows_are_upserted_by_date_key(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);
        $query = $this->createMock(Builder::class);

        $rows = [
            [
                'date_key' => 20261009,
                'calendar_date' => '2026-10-09',
                'calendar_year' => 2026,
                'calendar_quarter' => 4,
                'calendar_month' => 10,
                'month_name' => 'October',
                'iso_year' => 2026,
                'iso_week' => 41,
                'day_of_month' => 9,
                'iso_weekday' => 5,
                'is_weekend' => false,
            ],
        ];

        $database->expects($this->once())
            ->method('connection')
            ->with('warehouse')
            ->willReturn($connection);

        $connection->expects($this->once())
            ->method('table')
            ->with('dim_date')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('upsert')
            ->with(
                $rows,
                ['date_key'],
                $this->callback(
                    static fn (array $columns): bool => (
                        ! in_array('date_key', $columns, true)
                        && in_array(
                            'calendar_date',
                            $columns,
                            true,
                        )
                        && in_array(
                            'iso_year',
                            $columns,
                            true,
                        )
                        && in_array(
                            'is_weekend',
                            $columns,
                            true,
                        )
                    ),
                ),
            )
            ->willReturn(1);

        $writer = new DatabaseDateDimensionWriter($database);

        $writer->upsert($rows);
    }

    public function test_empty_batch_does_not_request_connection(): void
    {
        $database = $this->createMock(DatabaseManager::class);

        $database->expects($this->never())
            ->method('connection');

        (new DatabaseDateDimensionWriter($database))->upsert([]);
    }
}
