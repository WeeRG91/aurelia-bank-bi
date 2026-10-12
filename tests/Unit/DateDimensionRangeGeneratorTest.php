<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Dimensions\DateDimensionRangeGenerator;
use App\Analytics\Warehouse\Dimensions\DateDimensionRowFactory;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DateDimensionRangeGeneratorTest extends TestCase
{
    public function test_range_is_inclusive_and_contains_leap_day(): void
    {
        $rows = iterator_to_array(
            $this->generator()->generate(
                new DateTimeImmutable('2024-02-28'),
                new DateTimeImmutable('2024-03-01'),
            ),
            false,
        );

        $this->assertSame(
            [
                20240228,
                20240229,
                20240301,
            ],
            array_column($rows, 'date_key'),
        );

        $this->assertCount(3, $rows);
    }

    public function test_single_date_range_produces_one_row(): void
    {
        $rows = iterator_to_array(
            $this->generator()->generate(
                new DateTimeImmutable('2026-10-09 18:00:00'),
                new DateTimeImmutable('2026-10-09 06:00:00'),
            ),
            false,
        );

        $this->assertCount(1, $rows);
        $this->assertSame(
            20261009,
            $rows[0]['date_key'],
        );
    }

    public function test_reversed_range_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        iterator_to_array(
            $this->generator()->generate(
                new DateTimeImmutable('2026-12-31'),
                new DateTimeImmutable('2026-01-01'),
            ),
        );
    }

    private function generator(): DateDimensionRangeGenerator
    {
        return new DateDimensionRangeGenerator(
            new DateDimensionRowFactory,
        );
    }
}
