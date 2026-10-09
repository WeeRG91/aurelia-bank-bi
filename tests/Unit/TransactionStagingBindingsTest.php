<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Etl\BatchedTransactionStagingLoader;
use App\Analytics\Warehouse\Etl\DatabaseTransactionStagingBatchWriter;
use App\Analytics\Warehouse\Etl\DatabaseTransactionStagingSource;
use App\Analytics\Warehouse\Etl\TransactionStagingBatchWriter;
use App\Analytics\Warehouse\Etl\TransactionStagingLoader;
use App\Analytics\Warehouse\Etl\TransactionStagingPipeline;
use App\Analytics\Warehouse\Etl\TransactionStagingSource;
use Tests\TestCase;

final class TransactionStagingBindingsTest extends TestCase
{
    public function test_transaction_staging_services_are_bound(): void
    {
        $this->assertInstanceOf(
            DatabaseTransactionStagingSource::class,
            app(TransactionStagingSource::class),
        );

        $this->assertInstanceOf(
            BatchedTransactionStagingLoader::class,
            app(TransactionStagingLoader::class),
        );

        $this->assertInstanceOf(
            DatabaseTransactionStagingBatchWriter::class,
            app(TransactionStagingBatchWriter::class),
        );

        $this->assertInstanceOf(
            TransactionStagingPipeline::class,
            app(TransactionStagingPipeline::class),
        );
    }
}
