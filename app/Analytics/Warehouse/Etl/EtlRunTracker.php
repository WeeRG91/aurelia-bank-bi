<?php

namespace App\Analytics\Warehouse\Etl;

use App\Models\EtlLoadRun;
use DateTimeImmutable;

final readonly class EtlRunTracker
{
    public function __construct(
        private EtlRunStore $store,
        private EtlRunLifecycle $lifecycle,
    ) {}

    public function start(
        string $pipeline,
        DateTimeImmutable $startedAt,
    ): EtlLoadRun {
        return $this->store->create($pipeline, $startedAt);
    }

    public function succeed(
        EtlLoadRun $run,
        int $extractedRows,
        int $loadedRows,
        DateTimeImmutable $finishedAt,
    ): void {
        $this->lifecycle->succeed(
            $run,
            $extractedRows,
            $loadedRows,
            $finishedAt,
        );

        $this->store->save($run);
    }

    public function fail(
        EtlLoadRun $run,
        int $extractedRows,
        int $loadedRows,
        string $failureCode,
        DateTimeImmutable $finishedAt,
    ): void {
        $this->lifecycle->fail(
            $run,
            $extractedRows,
            $loadedRows,
            $failureCode,
            $finishedAt,
        );

        $this->store->save($run);
    }
}
