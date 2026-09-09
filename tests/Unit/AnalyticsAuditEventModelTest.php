<?php

namespace Tests\Unit;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSource;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Datasets\DatasetKey;
use App\Models\AnalyticsAuditEvent;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class AnalyticsAuditEventModelTest extends TestCase
{
    public function test_it_casts_audit_event_state(): void
    {
        $event = (new AnalyticsAuditEvent)->setRawAttributes([
            'action' => AuditAction::REPORT_PREVIEWED->value,
            'outcome' => AuditOutcome::SUCCEEDED->value,
            'source' => AuditSource::WEB->value,
            'dataset' => DatasetKey::TRANSACTIONS->value,
            'context' => '{"dimension_count":2}',
            'occurred_at' => '2026-09-09 10:00:00+00',
            'subject_type' => AuditSubjectType::SAVED_REPORT->value,
        ]);

        $this->assertSame(
            AuditAction::REPORT_PREVIEWED,
            $event->action,
        );

        $this->assertSame(
            AuditOutcome::SUCCEEDED,
            $event->outcome,
        );

        $this->assertSame(
            AuditSource::WEB,
            $event->source,
        );

        $this->assertSame(
            DatasetKey::TRANSACTIONS,
            $event->dataset,
        );

        $this->assertSame(
            ['dimension_count' => 2],
            $event->context,
        );

        $this->assertInstanceOf(
            CarbonImmutable::class,
            $event->occurred_at,
        );

        $this->assertSame(
            AuditSubjectType::SAVED_REPORT,
            $event->subject_type,
        );

        $this->assertFalse($event->usesTimestamps());
        $this->assertFalse($event->getIncrementing());
        $this->assertSame('string', $event->getKeyType());
    }

    public function test_it_defines_the_actor_relationship(): void
    {
        $event = new AnalyticsAuditEvent;
        $relationship = $event->actor();

        $this->assertInstanceOf(
            Employee::class,
            $relationship->getRelated(),
        );

        $this->assertSame(
            'actor_employee_id',
            $relationship->getForeignKeyName(),
        );
    }
}
