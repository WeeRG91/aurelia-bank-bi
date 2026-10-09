<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Etl\EtlPipelineResult;
use App\Analytics\Warehouse\Etl\TransactionStagingLoader;
use App\Analytics\Warehouse\Etl\TransactionStagingPipeline;
use App\Analytics\Warehouse\Etl\TransactionStagingSource;
use PHPUnit\Framework\TestCase;

final class TransactionStagingPipelineTest extends TestCase
{
    public function test_pipeline_identifier_is_stable(): void
    {
        $source = $this->createStub(
            TransactionStagingSource::class,
        );
        $loader = $this->createStub(
            TransactionStagingLoader::class,
        );

        $pipeline = new TransactionStagingPipeline(
            $source,
            $loader,
        );

        $this->assertSame(
            'transactions_staging',
            $pipeline->identifier(),
        );
    }

    public function test_extracted_rows_are_forwarded_to_loader(): void
    {
        $sourceRows = [
            (object) [
                'source_transaction_id' => 101,
            ],
            (object) [
                'source_transaction_id' => 102,
            ],
        ];

        $expectedResult = new EtlPipelineResult(
            extractedRows: 2,
            loadedRows: 2,
        );

        $source = $this->createMock(
            TransactionStagingSource::class,
        );
        $loader = $this->createMock(
            TransactionStagingLoader::class,
        );

        $source->expects($this->once())
            ->method('extract')
            ->willReturn($sourceRows);

        $loader->expects($this->once())
            ->method('load')
            ->with($sourceRows)
            ->willReturn($expectedResult);

        $pipeline = new TransactionStagingPipeline(
            $source,
            $loader,
        );

        $this->assertSame(
            $expectedResult,
            $pipeline->run(),
        );
    }
}
