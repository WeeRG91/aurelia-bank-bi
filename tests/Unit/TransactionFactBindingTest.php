<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Facts\DatabaseTransactionFactSource;
use App\Analytics\Warehouse\Facts\DatabaseTransactionFactWriter;
use App\Analytics\Warehouse\Facts\TransactionFactLoader;
use App\Analytics\Warehouse\Facts\TransactionFactSource;
use App\Analytics\Warehouse\Facts\TransactionFactWriter;
use Tests\TestCase;

final class TransactionFactBindingTest extends TestCase
{
    public function test_transaction_fact_services_are_resolvable(): void
    {
        $this->assertInstanceOf(
            DatabaseTransactionFactSource::class,
            $this->app->make(TransactionFactSource::class),
        );

        $this->assertInstanceOf(
            DatabaseTransactionFactWriter::class,
            $this->app->make(TransactionFactWriter::class),
        );

        $this->assertInstanceOf(
            TransactionFactLoader::class,
            $this->app->make(TransactionFactLoader::class),
        );
    }
}
