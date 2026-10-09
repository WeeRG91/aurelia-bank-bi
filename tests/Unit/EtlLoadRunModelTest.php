<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Etl\EtlRunStatus;
use App\Models\EtlLoadRun;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class EtlLoadRunModelTest extends TestCase
{
    public function test_it_uses_the_warehouse_boundary(): void
    {
        $run = new EtlLoadRun;

        $this->assertSame(
            'warehouse',
            $run->getConnectionName(),
        );

        $this->assertSame(
            'etl_load_runs',
            $run->getTable(),
        );

        $this->assertFalse(
            $run->getIncrementing(),
        );

        $this->assertSame(
            'string',
            $run->getKeyType(),
        );
    }

    public function test_it_casts_etl_execution_state(): void
    {
        $run = (new EtlLoadRun)->setRawAttributes([
            'status' => 'running',
            'extracted_rows' => '125',
            'loaded_rows' => '120',
            'started_at' => '2026-10-08 14:00:00+00',
            'finished_at' => null,
        ]);

        $this->assertSame(
            EtlRunStatus::RUNNING,
            $run->status,
        );

        $this->assertSame(
            125,
            $run->extracted_rows,
        );

        $this->assertSame(
            120,
            $run->loaded_rows,
        );

        $this->assertInstanceOf(
            CarbonImmutable::class,
            $run->started_at,
        );

        $this->assertNull(
            $run->finished_at,
        );
    }
}
