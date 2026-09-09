<?php

namespace Tests\Unit;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\ScheduledReportExportFailed;
use App\Notifications\ScheduledReportExportReady;
use Tests\TestCase;

final class ScheduledReportNotificationSecurityTest extends TestCase
{
    public function test_active_employee_can_receive_scheduled_report_mail(): void
    {
        $user = $this->userWithStatus(EmployeeStatus::ACTIVE);

        $this->assertTrue(
            $this->readyNotification()->shouldSend($user, 'mail'),
        );

        $this->assertTrue(
            $this->failedNotification()->shouldSend($user, 'mail'),
        );
    }

    public function test_inactive_employee_cannot_receive_scheduled_report_mail(): void
    {
        foreach ([
            EmployeeStatus::INACTIVE,
            EmployeeStatus::SUSPENDED,
            EmployeeStatus::TERMINATED,
        ] as $status) {
            $user = $this->userWithStatus($status);

            $this->assertFalse(
                $this->readyNotification()->shouldSend($user, 'mail'),
            );

            $this->assertFalse(
                $this->failedNotification()->shouldSend($user, 'mail'),
            );
        }
    }

    public function test_scheduled_report_notifications_only_use_mail_channel(): void
    {
        $user = $this->userWithStatus(EmployeeStatus::ACTIVE);

        $this->assertFalse(
            $this->readyNotification()->shouldSend(
                $user,
                'database',
            ),
        );

        $this->assertFalse(
            $this->failedNotification()->shouldSend(
                $user,
                'database',
            ),
        );
    }

    private function userWithStatus(
        EmployeeStatus $status,
    ): User {
        $employee = (new Employee)->forceFill([
            'status' => $status,
        ]);

        return (new User)
            ->forceFill([
                'name' => 'Aurelia Employee',
                'email' => 'employee@aurelia.test',
            ])
            ->setRelation('employee', $employee);
    }

    private function readyNotification(): ScheduledReportExportReady
    {
        return new ScheduledReportExportReady(
            exportId: '01K4N5QJZ0H4V3MTEXAMPLE123',
            reportName: 'Daily transaction activity',
            format: 'csv',
            rowCount: 1250,
            expiresAt: '2026-09-16 10:00 CEST',
        );
    }

    private function failedNotification(): ScheduledReportExportFailed
    {
        return new ScheduledReportExportFailed(
            exportId: '01K4N5QJZ0H4V3MTEXAMPLE123',
            reportName: 'Daily transaction activity',
        );
    }
}
