<?php

namespace App\Analytics\Warehouse\Etl;

use App\Models\EtlLoadRun;
use Carbon\CarbonImmutable;
use Throwable;

final readonly class EtlPipelineExecutor
{
    public function __construct(
        private EtlRunTracker $tracker,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(
        EtlPipeline $pipeline,
    ): EtlLoadRun {
        $run = $this->tracker->start(
            pipeline: $pipeline->identifier(),
            startedAt: CarbonImmutable::now('UTC'),
        );

        try {
            $result = $pipeline->run();
        } catch (Throwable $exception) {
            $this->tracker->fail(
                run: $run,
                extractedRows: 0,
                loadedRows: 0,
                failureCode: 'pipeline_execution_failed',
                finishedAt: CarbonImmutable::now('UTC'),
            );

            throw $exception;
        }

        $this->tracker->succeed(
            run: $run,
            extractedRows: $result->extractedRows,
            loadedRows: $result->loadedRows,
            finishedAt: CarbonImmutable::now('UTC'),
        );

        return $run;
    }
}
