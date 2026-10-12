<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Dimensions\AccountDimensionLoader;
use App\Analytics\Warehouse\Dimensions\AccountDimensionSource;
use App\Analytics\Warehouse\Dimensions\AccountDimensionWriter;
use App\Analytics\Warehouse\Dimensions\DatabaseAccountDimensionSource;
use App\Analytics\Warehouse\Dimensions\DatabaseAccountDimensionWriter;
use Tests\TestCase;

final class AccountDimensionBindingTest extends TestCase
{
    public function test_account_dimension_services_are_resolvable(): void
    {
        $this->assertInstanceOf(
            DatabaseAccountDimensionSource::class,
            $this->app->make(AccountDimensionSource::class),
        );

        $this->assertInstanceOf(
            DatabaseAccountDimensionWriter::class,
            $this->app->make(AccountDimensionWriter::class),
        );

        $this->assertInstanceOf(
            AccountDimensionLoader::class,
            $this->app->make(AccountDimensionLoader::class),
        );
    }
}
