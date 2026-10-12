<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Dimensions\DateDimensionLoader;
use App\Analytics\Warehouse\Dimensions\DateDimensionRangeGenerator;
use App\Analytics\Warehouse\Dimensions\DateDimensionRowFactory;
use App\Analytics\Warehouse\Dimensions\DateDimensionWriter;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DateDimensionLoaderTest extends TestCase
{
    public function test_date_rows_are_loaded_in_bounded_batches(): void
    {
        $writer = new InMemoryDateDimensionWriter;
        $loader = $this->loader($writer);
        $start = new DateTimeImmutable('2024-01-01');

        $loadedRows = $loader->load(
            $start,
            $start->modify('+500 days'),
        );

        $this->assertSame(501, $loadedRows);
        $this->assertSame(1, $writer->transactionCount);
        $this->assertSame(
            [500, 1],
            array_map(
                count(...),
                $writer->batches,
            ),
        );
    }

    public function test_single_date_is_loaded_once(): void
    {
        $writer = new InMemoryDateDimensionWriter;
        $loader = $this->loader($writer);

        $loadedRows = $loader->load(
            new DateTimeImmutable('2026-10-09'),
            new DateTimeImmutable('2026-10-09'),
        );

        $this->assertSame(1, $loadedRows);
        $this->assertCount(1, $writer->batches);
        $this->assertSame(
            20261009,
            $writer->batches[0][0]['date_key'],
        );
    }

    private function loader(
        DateDimensionWriter $writer,
    ): DateDimensionLoader {
        return new DateDimensionLoader(
            generator: new DateDimensionRangeGenerator(
                new DateDimensionRowFactory,
            ),
            writer: $writer,
        );
    }
}

/**
 * @internal
 */
final class InMemoryDateDimensionWriter implements DateDimensionWriter
{
    public int $transactionCount = 0;

    /**
     * @var list<list<array<string, int|string|bool>>>
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
