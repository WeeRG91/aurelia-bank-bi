<?php

namespace Tests\Feature;

use App\Analytics\Warehouse\Dimensions\AccountDimensionSource;
use App\Analytics\Warehouse\Dimensions\AccountDimensionWriter;
use Tests\TestCase;

final class LoadAccountDimensionCommandTest extends TestCase
{
    public function test_staged_accounts_are_loaded(): void
    {
        $source = new class implements AccountDimensionSource
        {
            public function rows(): iterable
            {
                yield [
                    'source_account_id' => 1,
                    'account_number' => 'AUR-LU-000000000001',
                    'account_type' => 'current',
                    'account_currency' => 'EUR',
                    'account_opened_at' => '2021-01-15',
                    'account_closed_at' => null,
                    'account_status' => 'active',
                ];

                yield [
                    'source_account_id' => 2,
                    'account_number' => 'AUR-LU-000000000002',
                    'account_type' => 'savings',
                    'account_currency' => 'EUR',
                    'account_opened_at' => '2022-06-20',
                    'account_closed_at' => null,
                    'account_status' => 'active',
                ];
            }
        };

        $writer = new class implements AccountDimensionWriter
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
            AccountDimensionSource::class,
            $source,
        );

        $this->app->instance(
            AccountDimensionWriter::class,
            $writer,
        );

        $this->artisan('warehouse:load-account-dimension')
            ->expectsOutputToContain(
                'Account dimension loaded successfully: 2 rows.',
            )
            ->assertSuccessful();

        $this->assertSame(1, $writer->transactionCount);
        $this->assertCount(2, $writer->rows);

        $this->assertSame(
            [1, 2],
            array_column(
                $writer->rows,
                'source_account_id',
            ),
        );
    }
}
