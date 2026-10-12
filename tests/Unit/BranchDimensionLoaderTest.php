<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Dimensions\BranchDimensionLoader;
use App\Analytics\Warehouse\Dimensions\BranchDimensionRowFactory;
use App\Analytics\Warehouse\Dimensions\BranchDimensionSource;
use App\Analytics\Warehouse\Dimensions\BranchDimensionWriter;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BranchDimensionLoaderTest extends TestCase
{
    public function test_source_rows_are_loaded_transactionally(): void
    {
        $source = new class implements BranchDimensionSource
        {
            public function rows(): iterable
            {
                yield [
                    'source_branch_id' => 1,
                    'branch_code' => 'LUX-CENTRAL',
                    'branch_name' => 'Luxembourg Central Branch',
                    'branch_country_code' => 'LU',
                    'branch_city' => 'Luxembourg',
                ];

                yield [
                    'source_branch_id' => 2,
                    'branch_code' => 'ESCH-SOUTH',
                    'branch_name' => 'Esch South Branch',
                    'branch_country_code' => 'LU',
                    'branch_city' => 'Esch-sur-Alzette',
                ];
            }
        };

        $writer = new class implements BranchDimensionWriter
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

        $loadedRows = (new BranchDimensionLoader(
            $source,
            new BranchDimensionRowFactory,
            $writer,
        ))->load(
            new DateTimeImmutable(
                '2026-10-09T15:00:00+02:00',
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
                'source_branch_id',
            ),
        );

        $this->assertSame(
            '2026-10-09 13:00:00+00:00',
            $writer->batches[0][0]['loaded_at'],
        );
    }

    public function test_empty_source_loads_no_rows(): void
    {
        $source = new class implements BranchDimensionSource
        {
            public function rows(): iterable
            {
                return [];
            }
        };

        $writer = new class implements BranchDimensionWriter
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

        $loadedRows = (new BranchDimensionLoader(
            $source,
            new BranchDimensionRowFactory,
            $writer,
        ))->load(new DateTimeImmutable('2026-10-09T13:00:00Z'));

        $this->assertSame(0, $loadedRows);
        $this->assertSame(0, $writer->upsertCount);
    }
}
