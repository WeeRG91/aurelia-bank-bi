<?php

namespace Tests\Unit;

use App\Notifications\ScheduledReportExportReady;
use Tests\TestCase;

final class HorizonNotificationQueueTest extends TestCase
{
    public function test_notifications_use_a_dedicated_redis_supervisor(): void
    {
        $supervisor = config(
            'horizon.defaults.supervisor-notifications',
        );

        $this->assertIsArray($supervisor);
        $this->assertSame('redis', $supervisor['connection']);
        $this->assertSame(
            ['notifications'],
            $supervisor['queue'],
        );
        $this->assertSame(60, $supervisor['timeout']);

        $this->assertSame(
            1,
            config(
                'horizon.environments.local.supervisor-notifications.maxProcesses',
            ),
        );

        $this->assertSame(
            2,
            config(
                'horizon.environments.production.supervisor-notifications.maxProcesses',
            ),
        );
    }

    public function test_notification_timeouts_finish_before_redis_retry(): void
    {
        $notification = new ScheduledReportExportReady(
            exportId: '01K4N5QJZ0H4V3MTEXAMPLE123',
            reportName: 'Daily transaction activity',
            format: 'csv',
            rowCount: 1250,
            expiresAt: '2026-09-16 10:00 CEST',
        );

        $workerTimeout = (int) config(
            'horizon.defaults.supervisor-notifications.timeout',
        );

        $retryAfter = (int) config(
            'queue.connections.redis.retry_after',
        );

        $this->assertTrue(
            $notification->timeout < $workerTimeout,
            'Notification timeout must be shorter than its Horizon worker timeout.',
        );

        $this->assertTrue(
            $workerTimeout < $retryAfter,
            'Horizon timeout must be shorter than Redis retry_after.',
        );
    }
}
