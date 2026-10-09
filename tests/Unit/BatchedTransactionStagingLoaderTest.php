<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Etl\BatchedTransactionStagingLoader;
use App\Analytics\Warehouse\Etl\TransactionStagingBatchWriter;
use App\Analytics\Warehouse\Etl\TransactionStagingTransformer;
use PHPUnit\Framework\TestCase;

final class BatchedTransactionStagingLoaderTest extends TestCase
{
    public function test_rows_are_loaded_in_bounded_batches(): void
    {
        $writer = new InMemoryTransactionStagingBatchWriter;
        $loader = new BatchedTransactionStagingLoader(
            new TransactionStagingTransformer,
            $writer,
        );

        $sourceRows = (function (): iterable {
            for ($id = 1; $id <= 501; $id++) {
                yield $this->sourceRow($id);
            }
        })();

        $result = $loader->load($sourceRows);

        $this->assertSame(501, $result->extractedRows);
        $this->assertSame(501, $result->loadedRows);
        $this->assertSame(1, $writer->transactionCount);
        $this->assertSame(
            [500, 1],
            array_map(
                count(...),
                $writer->batches,
            ),
        );
    }

    public function test_empty_source_produces_zero_counts(): void
    {
        $writer = new InMemoryTransactionStagingBatchWriter;
        $loader = new BatchedTransactionStagingLoader(
            new TransactionStagingTransformer,
            $writer,
        );

        $result = $loader->load([]);

        $this->assertSame(0, $result->extractedRows);
        $this->assertSame(0, $result->loadedRows);
        $this->assertSame(1, $writer->transactionCount);
        $this->assertSame([], $writer->batches);
    }

    private function sourceRow(int $id): object
    {
        return (object) [
            'source_transaction_id' => $id,
            'source_account_id' => 101,
            'source_branch_id' => 10,
            'transaction_reference' => sprintf(
                'TXN-%020d',
                $id,
            ),
            'transaction_type' => 'transfer',
            'category' => 'internal_transfer',
            'amount' => '100.00',
            'transaction_currency' => 'EUR',
            'direction' => 'outgoing',
            'booked_at' => '2026-10-08 12:30:00+00',
            'value_date' => '2026-10-08',
            'transaction_status' => 'booked',
            'account_number' => 'AUR-LU-000000000101',
            'account_type' => 'current',
            'account_currency' => 'EUR',
            'account_opened_at' => '2025-01-15',
            'account_closed_at' => null,
            'account_status' => 'active',
            'branch_code' => 'LU-LUX-001',
            'branch_name' => 'Luxembourg Central',
            'branch_country_code' => 'LU',
            'branch_city' => 'Luxembourg',
            'source_updated_at' => '2026-10-08 12:31:00',
        ];
    }
}

/**
 * @internal
 */
final class InMemoryTransactionStagingBatchWriter implements TransactionStagingBatchWriter
{
    public int $transactionCount = 0;

    /**
     * @var list<list<array<string, int|string|null>>>
     */
    public array $batches = [];

    public function transaction(callable $callback): mixed
    {
        $this->transactionCount++;

        return $callback();
    }

    public function upsert(array $rows): void
    {
        $this->batches[] = $rows;
    }
}
