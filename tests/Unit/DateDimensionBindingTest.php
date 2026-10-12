<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Dimensions\DatabaseDateDimensionWriter;
use App\Analytics\Warehouse\Dimensions\DateDimensionLoader;
use App\Analytics\Warehouse\Dimensions\DateDimensionWriter;
use Tests\TestCase;

final class DateDimensionBindingTest extends TestCase
{
    public function test_date_dimension_services_are_resolvable(): void
    {
        $this->assertInstanceOf(
            DatabaseDateDimensionWriter::class,
            app(DateDimensionWriter::class),
        );

        $this->assertInstanceOf(
            DateDimensionLoader::class,
            app(DateDimensionLoader::class),
        );
    }
}
