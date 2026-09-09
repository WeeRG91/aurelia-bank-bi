<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\ScheduledReportExportReady;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tests\TestCase;

final class ScheduledReportExportReadyTest extends TestCase
{
    public function test_it_builds_a_secure_queued_mail_notification(): void
    {
        $user = (new User)->forceFill([
            'name' => 'Aurelia Branch Analyst',
            'email' => 'branch.analyst@aurelia.test',
        ]);

        $notification = new ScheduledReportExportReady(
            exportId: '01K4N5QJZ0H4V3MTEXAMPLE123',
            reportName: 'Daily transaction activity',
            format: 'csv',
            rowCount: 1250,
            expiresAt: '2026-09-16 10:00 CEST',
        );

        $message = $notification->toMail($user);

        $this->assertInstanceOf(
            ShouldQueue::class,
            $notification,
        );

        $this->assertSame(
            ['mail'],
            $notification->via($user),
        );

        $this->assertSame(
            ['mail' => 'redis'],
            $notification->viaConnections(),
        );

        $this->assertSame(
            ['mail' => 'notifications'],
            $notification->viaQueues(),
        );

        $this->assertSame(
            'Your scheduled report is ready',
            $message->subject,
        );

        $this->assertSame(
            'Download report',
            $message->actionText,
        );

        $this->assertSame(
            route(
                'analytics.report-exports.download',
                ['reportExport' => $notification->exportId],
            ),
            $message->actionUrl,
        );
    }
}
