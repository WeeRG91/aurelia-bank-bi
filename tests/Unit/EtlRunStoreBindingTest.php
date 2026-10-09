<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Etl\EloquentEtlRunStore;
use App\Analytics\Warehouse\Etl\EtlRunStore;
use Tests\TestCase;

final class EtlRunStoreBindingTest extends TestCase
{
    public function test_etl_run_store_resolves_to_eloquent_implementation(): void
    {
        $this->assertInstanceOf(
            EloquentEtlRunStore::class,
            app(EtlRunStore::class),
        );
    }
}
