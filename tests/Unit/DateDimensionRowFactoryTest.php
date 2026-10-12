<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Dimensions\DateDimensionRowFactory;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DateDimensionRowFactoryTest extends TestCase
{
    public function test_calendar_date_is_transformed_into_dimension_row(): void
    {
        $row = (new DateDimensionRowFactory)->create(
            new DateTimeImmutable('2026-10-09'),
        );

        $this->assertSame([
            'date_key' => 20261009,
            'calendar_date' => '2026-10-09',
            'calendar_year' => 2026,
            'calendar_quarter' => 4,
            'calendar_month' => 10,
            'month_name' => 'October',
            'iso_year' => 2026,
            'iso_week' => 41,
            'day_of_month' => 9,
            'iso_weekday' => 5,
            'is_weekend' => false,
        ], $row);
    }

    public function test_iso_year_can_differ_from_calendar_year(): void
    {
        $row = (new DateDimensionRowFactory)->create(
            new DateTimeImmutable('2021-01-01'),
        );

        $this->assertSame(2021, $row['calendar_year']);
        $this->assertSame(2020, $row['iso_year']);
        $this->assertSame(53, $row['iso_week']);
    }

    public function test_weekend_is_derived_from_iso_weekday(): void
    {
        $row = (new DateDimensionRowFactory)->create(
            new DateTimeImmutable('2026-10-10'),
        );

        $this->assertSame(6, $row['iso_weekday']);
        $this->assertTrue($row['is_weekend']);
    }
}
