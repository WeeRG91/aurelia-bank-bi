<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Etl\EtlRunLifecycle;
use App\Analytics\Warehouse\Etl\EtlRunStatus;
use App\Analytics\Warehouse\Etl\EtlRunStore;
use App\Analytics\Warehouse\Etl\EtlRunTracker;
use App\Models\EtlLoadRun;
use DateTimeImmutable;
use LogicException;
use Tests\TestCase;

final class EtlRunTrackerTest extends TestCase
{
    public function test_it_starts_a_tracked_run(): void
    {
        $startedAt = new DateTimeImmutable(
            '2026-10-08T14:00:00+00:00',
        );
        $run = $this->runningRun();
        $store = $this->createMock(EtlRunStore::class);

        $store->expects($this->once())
            ->method('create')
            ->with('transactions_etl', $startedAt)
            ->willReturn($run);

        $result = $this->tracker($store)->start(
            'transactions_etl',
            $startedAt,
        );

        $this->assertSame($run, $result);
    }

    public function test_it_persists_a_successful_transition(): void
    {
        $run = $this->runningRun();
        $store = $this->createMock(EtlRunStore::class);

        $store->expects($this->once())
            ->method('save')
            ->with($run);

        $this->tracker($store)->succeed(
            run: $run,
            extractedRows: 100,
            loadedRows: 100,
            finishedAt: new DateTimeImmutable(
                '2026-10-08T14:05:00+00:00',
            ),
        );

        $this->assertSame(EtlRunStatus::SUCCEEDED, $run->status);
        $this->assertSame(100, $run->extracted_rows);
        $this->assertSame(100, $run->loaded_rows);
    }

    public function test_it_persists_a_failed_transition(): void
    {
        $run = $this->runningRun();
        $store = $this->createMock(EtlRunStore::class);

        $store->expects($this->once())
            ->method('save')
            ->with($run);

        $this->tracker($store)->fail(
            run: $run,
            extractedRows: 100,
            loadedRows: 75,
            failureCode: 'warehouse_insert_failed',
            finishedAt: new DateTimeImmutable(
                '2026-10-08T14:04:00+00:00',
            ),
        );

        $this->assertSame(EtlRunStatus::FAILED, $run->status);
        $this->assertSame(
            'warehouse_insert_failed',
            $run->failure_code,
        );
    }

    public function test_invalid_transition_is_not_persisted(): void
    {
        $run = $this->runningRun();

        $run->forceFill([
            'status' => EtlRunStatus::SUCCEEDED,
            'finished_at' => new DateTimeImmutable(
                '2026-10-08T14:05:00+00:00',
            ),
        ]);

        $store = $this->createMock(EtlRunStore::class);

        $store->expects($this->never())
            ->method('save');

        $this->expectException(LogicException::class);

        $this->tracker($store)->fail(
            run: $run,
            extractedRows: 100,
            loadedRows: 75,
            failureCode: 'unexpected_failure',
            finishedAt: new DateTimeImmutable(
                '2026-10-08T14:06:00+00:00',
            ),
        );
    }

    private function tracker(EtlRunStore $store): EtlRunTracker
    {
        return new EtlRunTracker(
            store: $store,
            lifecycle: new EtlRunLifecycle,
        );
    }

    private function runningRun(): EtlLoadRun
    {
        return (new EtlLoadRun)->setRawAttributes([
            'status' => 'running',
            'extracted_rows' => 0,
            'loaded_rows' => 0,
            'failure_code' => null,
            'started_at' => '2026-10-08T14:00:00+00:00',
            'finished_at' => null,
        ]);
    }
}
