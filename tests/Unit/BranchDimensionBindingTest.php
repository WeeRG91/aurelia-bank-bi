<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Dimensions\BranchDimensionLoader;
use App\Analytics\Warehouse\Dimensions\BranchDimensionSource;
use App\Analytics\Warehouse\Dimensions\BranchDimensionWriter;
use App\Analytics\Warehouse\Dimensions\DatabaseBranchDimensionSource;
use App\Analytics\Warehouse\Dimensions\DatabaseBranchDimensionWriter;
use Tests\TestCase;

final class BranchDimensionBindingTest extends TestCase
{
    public function test_branch_dimension_services_are_resolvable(): void
    {
        $this->assertInstanceOf(
            DatabaseBranchDimensionSource::class,
            $this->app->make(BranchDimensionSource::class),
        );

        $this->assertInstanceOf(
            DatabaseBranchDimensionWriter::class,
            $this->app->make(BranchDimensionWriter::class),
        );

        $this->assertInstanceOf(
            BranchDimensionLoader::class,
            $this->app->make(BranchDimensionLoader::class),
        );
    }
}
