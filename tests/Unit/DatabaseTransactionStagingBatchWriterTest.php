<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Etl\DatabaseTransactionStagingBatchWriter;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use PHPUnit\Framework\TestCase;

final class DatabaseTransactionStagingBatchWriterTest extends TestCase
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

        $writer = new DatabaseTransactionStagingBatchWriter(
            $database,
        );

        $result = $writer->transaction(
            static fn (): string => 'committed',
        );

        $this->assertSame('committed', $result);
    }

    public function test_rows_are_upserted_by_source_transaction_id(): void
    {
        $database = $this->createMock(DatabaseManager::class);
        $connection = $this->createMock(Connection::class);
        $query = $this->createMock(Builder::class);

        $rows = [
            [
                'source_transaction_id' => 501,
                'transaction_reference' => 'TXN-ABCDEFGHIJKLMNOPQRST',
                'loaded_at' => '2026-10-09 08:00:00+00:00',
            ],
        ];

        $database->expects($this->once())
            ->method('connection')
            ->with('warehouse')
            ->willReturn($connection);

        $connection->expects($this->once())
            ->method('table')
            ->with('stg_transactions')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('upsert')
            ->with(
                $rows,
                ['source_transaction_id'],
                $this->callback(
                    static fn (array $columns): bool => (
                        ! in_array(
                            'source_transaction_id',
                            $columns,
                            true,
                        )
                        && in_array(
                            'transaction_reference',
                            $columns,
                            true,
                        )
                        && in_array(
                            'source_updated_at',
                            $columns,
                            true,
                        )
                        && in_array(
                            'loaded_at',
                            $columns,
                            true,
                        )
                    ),
                ),
            )
            ->willReturn(1);

        $writer = new DatabaseTransactionStagingBatchWriter(
            $database,
        );

        $writer->upsert($rows);
    }

    public function test_empty_batch_does_not_request_a_connection(): void
    {
        $database = $this->createMock(DatabaseManager::class);

        $database->expects($this->never())
            ->method('connection');

        $writer = new DatabaseTransactionStagingBatchWriter(
            $database,
        );

        $writer->upsert([]);
    }
}
