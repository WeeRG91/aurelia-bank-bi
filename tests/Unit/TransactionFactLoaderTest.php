<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Facts\TransactionFactLoader;
use App\Analytics\Warehouse\Facts\TransactionFactRowFactory;
use App\Analytics\Warehouse\Facts\TransactionFactSource;
use App\Analytics\Warehouse\Facts\TransactionFactWriter;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class TransactionFactLoaderTest extends TestCase
{
    public function test_source_rows_are_loaded_transactionally(): void
    {
        $source = new class implements TransactionFactSource
        {
            public function rows(): iterable
            {
                yield [
                    'source_transaction_id' => 101,
                    'account_key' => 11,
                    'branch_key' => 2,
                    'booking_date_key' => 20261012,
                    'value_date_key' => 20261011,
                    'booked_at' => '2026-10-12T15:45:00+02:00',
                    'transaction_reference' => 'TXN-D0000000000000000101',
                    'transaction_type' => 'transfer',
                    'category' => 'salary',
                    'amount' => '1250.50',
                    'transaction_currency' => 'EUR',
                    'direction' => 'incoming',
                    'transaction_status' => 'booked',
                    'source_updated_at' => '2026-10-12 08:00:00',
                ];

                yield [
                    'source_transaction_id' => 102,
                    'account_key' => 12,
                    'branch_key' => 3,
                    'booking_date_key' => null,
                    'value_date_key' => null,
                    'booked_at' => null,
                    'transaction_reference' => 'TXN-D0000000000000000102',
                    'transaction_type' => 'fee',
                    'category' => 'bank_fee',
                    'amount' => '12.75',
                    'transaction_currency' => 'EUR',
                    'direction' => 'outgoing',
                    'transaction_status' => 'pending',
                    'source_updated_at' => null,
                ];
            }
        };

        $writer = new class implements TransactionFactWriter
        {
            /**
             * @var list<list<array<string, int|string|null>>>
             */
            public array $batches = [];

            public int $transactionCount = 0;

            public function transaction(callable $callback): mixed
            {
                $this->transactionCount++;

                return $callback();
            }

            public function upsert(array $rows): void
            {
                $this->batches[] = $rows;
            }
        };

        $loadedRows = (new TransactionFactLoader(
            $source,
            new TransactionFactRowFactory,
            $writer,
        ))->load(
            new DateTimeImmutable(
                '2026-10-12T15:00:00+02:00',
            ),
        );

        $this->assertSame(2, $loadedRows);
        $this->assertSame(1, $writer->transactionCount);
        $this->assertCount(1, $writer->batches);
        $this->assertCount(2, $writer->batches[0]);

        $this->assertSame(
            [101, 102],
            array_column(
                $writer->batches[0],
                'source_transaction_id',
            ),
        );

        $this->assertSame(
            ['1250.50', '-12.75'],
            array_column(
                $writer->batches[0],
                'signed_amount',
            ),
        );

        $this->assertSame(
            [
                '2026-10-12 13:45:00+00:00',
                null,
            ],
            array_column(
                $writer->batches[0],
                'booked_at',
            ),
        );

        $this->assertSame(
            '2026-10-12 13:00:00+00:00',
            $writer->batches[0][0]['loaded_at'],
        );
    }

    public function test_empty_source_loads_no_rows(): void
    {
        $source = new class implements TransactionFactSource
        {
            public function rows(): iterable
            {
                return [];
            }
        };

        $writer = new class implements TransactionFactWriter
        {
            public int $upsertCount = 0;

            public function transaction(callable $callback): mixed
            {
                return $callback();
            }

            public function upsert(array $rows): void
            {
                $this->upsertCount++;
            }
        };

        $loadedRows = (new TransactionFactLoader(
            $source,
            new TransactionFactRowFactory,
            $writer,
        ))->load(
            new DateTimeImmutable(
                '2026-10-12T13:00:00Z',
            ),
        );

        $this->assertSame(0, $loadedRows);
        $this->assertSame(0, $writer->upsertCount);
    }
}
