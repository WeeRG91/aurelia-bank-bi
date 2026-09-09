<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use LogicException;

final class ScheduledReportExportReady extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly string $exportId,
        public readonly string $reportName,
        public readonly string $format,
        public readonly int $rowCount,
        public readonly string $expiresAt,
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
            ->subject('Your scheduled report is ready')
            ->greeting("Hello {$notifiable->name},")
            ->line(
                "The scheduled report \"{$this->reportName}\" has finished generating.",
            )
            ->line(
                sprintf(
                    'Format: %s · Rows: %s',
                    strtoupper($this->format),
                    number_format($this->rowCount),
                ),
            )
            ->action(
                'Download report',
                route(
                    'analytics.report-exports.download',
                    ['reportExport' => $this->exportId],
                ),
            )
            ->line(
                "For security, you must sign in to Aurelia Bank BI before downloading. The file is available until {$this->expiresAt}.",
            );
    }
}
