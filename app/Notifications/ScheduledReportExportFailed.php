<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use LogicException;

final class ScheduledReportExportFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly string $exportId,
        public readonly string $reportName,
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return [
            'mail' => 'redis',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'notifications',
        ];
    }

    public function shouldSend(
        object $notifiable,
        string $channel,
    ): bool {
        return $channel === 'mail'
            && $notifiable instanceof User
            && $notifiable->isActiveEmployee();
    }

    public function toMail(object $notifiable): MailMessage
    {
        if (! $notifiable instanceof User) {
            throw new LogicException(
                'Scheduled report notifications require a user recipient.',
            );
        }

        return (new MailMessage)
            ->error()
            ->subject('A scheduled report could not be generated')
            ->greeting("Hello {$notifiable->name},")
            ->line(
                "The scheduled report \"{$this->reportName}\" could not be generated after several attempts.",
            )
            ->line(
                'No report file was delivered. The technical failure has been recorded for an administrator to investigate.',
            )
            ->action(
                'View export status',
                route('analytics.report-exports.index'),
            )
            ->line(
                'You do not need to recreate the schedule. Its next occurrence remains scheduled.',
            );
    }
}
