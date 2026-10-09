<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Etl\EtlRunStatus;
use PHPUnit\Framework\TestCase;

final class EtlRunStatusTest extends TestCase
{
    public function test_status_values_are_stable(): void
    {
        $this->assertSame(
            [
                'running',
                'succeeded',
                'failed',
            ],
            array_column(
                EtlRunStatus::cases(),
                'value',
            ),
        );
    }

    public function test_only_finished_states_are_terminal(): void
    {
        $this->assertFalse(
            EtlRunStatus::RUNNING->isTerminal(),
        );

        $this->assertTrue(
            EtlRunStatus::SUCCEEDED->isTerminal(),
        );

        $this->assertTrue(
            EtlRunStatus::FAILED->isTerminal(),
        );
    }

    public function test_running_load_can_only_finish_once(): void
    {
        $this->assertTrue(
            EtlRunStatus::RUNNING->canTransitionTo(
                EtlRunStatus::SUCCEEDED,
            ),
        );

        $this->assertTrue(
            EtlRunStatus::RUNNING->canTransitionTo(
                EtlRunStatus::FAILED,
            ),
        );

        foreach ([
            EtlRunStatus::SUCCEEDED,
            EtlRunStatus::FAILED,
        ] as $finishedStatus) {
            foreach (EtlRunStatus::cases() as $nextStatus) {
                $this->assertFalse(
                    $finishedStatus->canTransitionTo(
                        $nextStatus,
                    ),
                );
            }
        }
    }
}
