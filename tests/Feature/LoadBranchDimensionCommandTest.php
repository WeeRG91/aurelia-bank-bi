<?php

namespace Tests\Feature;

use App\Analytics\Warehouse\Dimensions\BranchDimensionSource;
use App\Analytics\Warehouse\Dimensions\BranchDimensionWriter;
use Tests\TestCase;

final class LoadBranchDimensionCommandTest extends TestCase
{
    public function test_staged_branches_are_loaded(): void
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
             * @var list<array<string, int|string|null>>
             */
            public array $rows = [];

            public int $transactionCount = 0;

            public function transaction(callable $callback): mixed
            {
                $this->transactionCount++;

                return $callback();
            }

            public function synchronize(array $rows): void
            {
                $this->rows = [
                    ...$this->rows,
                    ...$rows,
                ];
            }
        };

        $this->app->instance(
            BranchDimensionSource::class,
            $source,
        );

        $this->app->instance(
            BranchDimensionWriter::class,
            $writer,
        );

        $this->artisan('warehouse:load-branch-dimension')
            ->expectsOutputToContain(
                'Branch dimension loaded successfully: 2 rows.',
            )
            ->assertSuccessful();

        $this->assertSame(1, $writer->transactionCount);
        $this->assertCount(2, $writer->rows);

        $this->assertSame(
            [1, 2],
            array_column(
                $writer->rows,
                'source_branch_id',
            ),
        );
    }
}
