<?php

namespace Tests\Unit;

use App\Analytics\Exports\ReportExportStatus;
use PHPUnit\Framework\TestCase;

class ReportExportStatusTest extends TestCase
{
    public function test_export_statuses_have_stable_values(): void
    {
        $this->assertSame(
            [
                'queued',
                'processing',
                'completed',
                'failed',
                'expired',
            ],
            array_column(
                ReportExportStatus::cases(),
                'value',
            ),
        );
    }

    public function test_only_finished_states_are_terminal(): void
    {
        $this->assertFalse(
            ReportExportStatus::QUEUED->isTerminal(),
        );

        $this->assertFalse(
            ReportExportStatus::PROCESSING->isTerminal(),
        );

        $this->assertTrue(
            ReportExportStatus::COMPLETED->isTerminal(),
        );

        $this->assertTrue(
            ReportExportStatus::FAILED->isTerminal(),
        );

        $this->assertTrue(
            ReportExportStatus::EXPIRED->isTerminal(),
        );
    }
}
