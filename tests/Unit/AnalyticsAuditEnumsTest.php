<?php

namespace Tests\Unit;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSource;
use App\Analytics\Auditing\AuditSubjectType;
use PHPUnit\Framework\TestCase;

final class AnalyticsAuditEnumsTest extends TestCase
{
    public function test_audit_actions_have_stable_values(): void
    {
        $this->assertSame(
            [
                'dataset_inspected',
                'audit_trail_viewed',
                'report_opened',
                'report_previewed',
                'report_created',
                'report_updated',
                'report_deleted',
                'report_restored',
                'report_duplicated',
                'export_queued',
                'export_started',
                'export_completed',
                'export_failed',
                'export_downloaded',
                'schedule_created',
                'schedule_paused',
                'schedule_resumed',
                'schedule_dispatched',
                'schedule_auto_paused',
            ],
            array_column(AuditAction::cases(), 'value'),
        );
    }

    public function test_audit_outcomes_have_stable_values(): void
    {
        $this->assertSame(
            [
                'succeeded',
                'denied',
                'failed',
            ],
            array_column(AuditOutcome::cases(), 'value'),
        );
    }

    public function test_audit_sources_have_stable_values(): void
    {
        $this->assertSame(
            [
                'web',
                'queue',
                'scheduler',
                'system',
            ],
            array_column(AuditSource::cases(), 'value'),
        );
    }

    public function test_audit_subject_types_have_stable_values(): void
    {
        $this->assertSame(
            [
                'dataset',
                'saved_report',
                'report_export',
                'scheduled_report',
            ],
            array_column(AuditSubjectType::cases(), 'value'),
        );
    }
}
