<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Etl\EtlPipeline;
use App\Analytics\Warehouse\Etl\EtlPipelineExecutor;
use App\Analytics\Warehouse\Etl\EtlPipelineResult;
use App\Analytics\Warehouse\Etl\EtlRunLifecycle;
use App\Analytics\Warehouse\Etl\EtlRunStatus;
use App\Analytics\Warehouse\Etl\EtlRunStore;
use App\Analytics\Warehouse\Etl\EtlRunTracker;
use App\Models\EtlLoadRun;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use RuntimeException;
use Tests\TestCase;

final class EtlPipelineExecutorTest extends TestCase
{
    public function test_successful_pipeline_is_recorded(): void
    {
        $run = $this->runningRun();
        $store = $this->createMock(EtlRunStore::class);
        $pipeline = $this->createMock(EtlPipeline::class);

        $pipeline->expects($this->once())
            ->method('identifier')
            ->willReturn('transactions_staging');

        $pipeline->expects($this->once())
            ->method('run')
            ->willReturn(
                new EtlPipelineResult(
                    extractedRows: 125,
                    loadedRows: 120,
                ),
            );

        $store->expects($this->once())
            ->method('create')
            ->with(
                'transactions_staging',
                $this->isInstanceOf(DateTimeImmutable::class),
            )
            ->willReturn($run);

        $store->expects($this->once())
            ->method('save')
            ->with($run);

        $result = $this->executor($store)->execute($pipeline);

        $this->assertSame($run, $result);
        $this->assertSame(EtlRunStatus::SUCCEEDED, $run->status);
        $this->assertSame(125, $run->extracted_rows);
        $this->assertSame(120, $run->loaded_rows);
        $this->assertNull($run->failure_code);
    }

    public function test_failed_pipeline_is_recorded_and_rethrown(): void
    {
        $run = $this->runningRun();
        $store = $this->createMock(EtlRunStore::class);
        $pipeline = $this->createMock(EtlPipeline::class);

        $pipeline->method('identifier')
            ->willReturn('transactions_staging');

        $pipeline->method('run')
            ->willThrowException(
                new RuntimeException(
                    'Sensitive database error that must not be stored.',
                ),
            );

        $store->method('create')
            ->willReturn($run);

        $store->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(
                    static fn (EtlLoadRun $savedRun): bool => (
                        $savedRun->status === EtlRunStatus::FAILED
                        && $savedRun->failure_code ===
                        'pipeline_execution_failed'
                        && $savedRun->extracted_rows === 0
                        && $savedRun->loaded_rows === 0
                    ),
                ),
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Sensitive database error that must not be stored.',
        );

        $this->executor($store)->execute($pipeline);
    }

    private function executor(
        EtlRunStore $store,
    ): EtlPipelineExecutor {
        $tracker = new EtlRunTracker(
            store: $store,
            lifecycle: new EtlRunLifecycle,
        );

        return new EtlPipelineExecutor($tracker);
    }

    private function runningRun(): EtlLoadRun
    {
        return (new EtlLoadRun)->setRawAttributes([
            'status' => 'running',
            'extracted_rows' => 0,
            'loaded_rows' => 0,
            'failure_code' => null,
            'started_at' => CarbonImmutable::now('UTC')
                ->subMinute()
                ->toIso8601String(),
            'finished_at' => null,
        ]);
    }
}
