<?php

namespace Tests\Feature;

use App\Analytics\Warehouse\Facts\TransactionFactSource;
use App\Analytics\Warehouse\Facts\TransactionFactWriter;
use Tests\TestCase;

final class LoadTransactionFactsCommandTest extends TestCase
{
    public function test_staged_transactions_are_loaded(): void
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
             * @var list<array<string, int|string|null>>
             */
            public array $rows = [];

            public int $transactionCount = 0;

            public function transaction(callable $callback): mixed
            {
                $this->transactionCount++;

                return $callback();
            }

            public function upsert(array $rows): void
            {
                $this->rows = [
                    ...$this->rows,
                    ...$rows,
                ];
            }
        };

        $this->app->instance(
            TransactionFactSource::class,
            $source,
        );

        $this->app->instance(
            TransactionFactWriter::class,
            $writer,
        );

        $this->artisan('warehouse:load-transaction-facts')
            ->expectsOutputToContain(
                'Transaction facts loaded successfully: 2 rows.',
            )
            ->assertSuccessful();

        $this->assertSame(1, $writer->transactionCount);
        $this->assertCount(2, $writer->rows);

        $this->assertSame(
            [101, 102],
            array_column(
                $writer->rows,
                'source_transaction_id',
            ),
        );
    }
}
