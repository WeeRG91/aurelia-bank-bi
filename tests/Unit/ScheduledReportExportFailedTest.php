<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\ScheduledReportExportFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tests\TestCase;

final class ScheduledReportExportFailedTest extends TestCase
{
    public function test_it_builds_a_secure_queued_failure_email(): void
    {
        $user = (new User)->forceFill([
            'name' => 'Aurelia Branch Analyst',
            'email' => 'branch.analyst@aurelia.test',
        ]);

        $notification = new ScheduledReportExportFailed(
            exportId: '01K4N5QJZ0H4V3MTEXAMPLE123',
            reportName: 'Daily transaction activity',
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
            'A scheduled report could not be generated',
            $message->subject,
        );

        $this->assertSame('error', $message->level);

        $this->assertSame(
            'View export status',
            $message->actionText,
        );

        $this->assertSame(
            route('analytics.report-exports.index'),
            $message->actionUrl,
        );

        $contents = implode(' ', $message->introLines);

        $this->assertStringContainsString(
            'Daily transaction activity',
            $contents,
        );

        $this->assertStringNotContainsString(
            'SQLSTATE',
            $contents,
        );
    }
}
