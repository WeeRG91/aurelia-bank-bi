<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Dimensions\AccountDimensionLoader;
use App\Analytics\Warehouse\Dimensions\AccountDimensionRowFactory;
use App\Analytics\Warehouse\Dimensions\AccountDimensionSource;
use App\Analytics\Warehouse\Dimensions\AccountDimensionWriter;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AccountDimensionLoaderTest extends TestCase
{
    public function test_source_rows_are_loaded_transactionally(): void
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
                    'account_closed_at' => '2026-08-31',
                    'account_status' => 'closed',
                ];
            }
        };

        $writer = new class implements AccountDimensionWriter
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

        $loadedRows = (new AccountDimensionLoader(
            $source,
            new AccountDimensionRowFactory,
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
            [1, 2],
            array_column(
                $writer->batches[0],
                'source_account_id',
            ),
        );

        $this->assertSame(
            '2026-10-12 13:00:00+00:00',
            $writer->batches[0][0]['loaded_at'],
        );
    }

    public function test_empty_source_loads_no_rows(): void
    {
        $source = new class implements AccountDimensionSource
        {
            public function rows(): iterable
            {
                return [];
            }
        };

        $writer = new class implements AccountDimensionWriter
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

        $loadedRows = (new AccountDimensionLoader(
            $source,
            new AccountDimensionRowFactory,
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
