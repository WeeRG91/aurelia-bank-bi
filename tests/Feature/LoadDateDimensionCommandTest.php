<?php

namespace Tests\Feature;

use App\Analytics\Warehouse\Dimensions\DateDimensionWriter;
use Tests\TestCase;

final class LoadDateDimensionCommandTest extends TestCase
{
    public function test_configured_date_range_is_loaded(): void
    {
        config([
            'analytics.warehouse.date_dimension_start' => '2026-01-01',
            'analytics.warehouse.date_dimension_end' => '2026-01-03',
        ]);

        $writer = new class implements DateDimensionWriter
        {
            /**
             * @var list<list<array<string, bool|int|string>>>
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

        $this->app->instance(
            DateDimensionWriter::class,
            $writer,
        );

        $this->artisan('warehouse:load-date-dimension')
            ->expectsOutputToContain(
                'Date dimension loaded successfully: 3 rows.',
            )
            ->assertSuccessful();

        $this->assertSame(1, $writer->transactionCount);
        $this->assertCount(1, $writer->batches);
        $this->assertCount(3, $writer->batches[0]);

        $this->assertSame(
            [20260101, 20260102, 20260103],
            array_column($writer->batches[0], 'date_key'),
        );
    }

    public function test_invalid_configured_date_is_rejected(): void
    {
        config([
            'analytics.warehouse.date_dimension_start' => '2026-02-31',
            'analytics.warehouse.date_dimension_end' => '2026-12-31',
        ]);

        $this->artisan('warehouse:load-date-dimension')
            ->expectsOutputToContain(
                'must contain a valid YYYY-MM-DD date',
            )
            ->assertFailed();
    }
}
