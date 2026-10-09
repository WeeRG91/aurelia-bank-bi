<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Etl\EtlRunLifecycle;
use App\Analytics\Warehouse\Etl\EtlRunStatus;
use App\Models\EtlLoadRun;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

final class EtlRunLifecycleTest extends TestCase
{
    public function test_running_load_can_succeed(): void
    {
        $run = $this->runningRun();

        (new EtlRunLifecycle)->succeed(
            run: $run,
            extractedRows: 125,
            loadedRows: 120,
            finishedAt: new DateTimeImmutable(
                '2026-10-08T14:05:00+00:00',
            ),
        );

        $this->assertSame(EtlRunStatus::SUCCEEDED, $run->status);
        $this->assertSame(125, $run->extracted_rows);
        $this->assertSame(120, $run->loaded_rows);
        $this->assertNull($run->failure_code);
        $this->assertNotNull($run->finished_at);
    }

    public function test_running_load_can_fail_with_safe_code(): void
    {
        $run = $this->runningRun();

        (new EtlRunLifecycle)->fail(
            run: $run,
            extractedRows: 125,
            loadedRows: 80,
            failureCode: 'warehouse_insert_failed',
            finishedAt: new DateTimeImmutable(
                '2026-10-08T14:04:00+00:00',
            ),
        );

        $this->assertSame(EtlRunStatus::FAILED, $run->status);
        $this->assertSame('warehouse_insert_failed', $run->failure_code);
        $this->assertSame(125, $run->extracted_rows);
        $this->assertSame(80, $run->loaded_rows);
    }

    public function test_invalid_row_counts_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new EtlRunLifecycle)->succeed(
            run: $this->runningRun(),
            extractedRows: 10,
            loadedRows: 11,
            finishedAt: new DateTimeImmutable(
                '2026-10-08T14:05:00+00:00',
            ),
        );
    }

    public function test_unsafe_failure_code_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new EtlRunLifecycle)->fail(
            run: $this->runningRun(),
            extractedRows: 10,
            loadedRows: 5,
            failureCode: 'Database connection failed!',
            finishedAt: new DateTimeImmutable(
                '2026-10-08T14:05:00+00:00',
            ),
        );
    }

    public function test_terminal_load_cannot_transition_again(): void
    {
        $run = $this->runningRun();

        $run->forceFill([
            'status' => EtlRunStatus::SUCCEEDED,
            'finished_at' => new DateTimeImmutable(
                '2026-10-08T14:05:00+00:00',
            ),
        ]);

        $this->expectException(LogicException::class);

        (new EtlRunLifecycle)->fail(
            run: $run,
            extractedRows: 10,
            loadedRows: 5,
            failureCode: 'unexpected_failure',
            finishedAt: new DateTimeImmutable(
                '2026-10-08T14:06:00+00:00',
            ),
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
