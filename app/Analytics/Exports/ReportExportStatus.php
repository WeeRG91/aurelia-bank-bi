<?php

namespace App\Analytics\Exports;

enum ReportExportStatus: string
{
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case EXPIRED = 'expired';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::COMPLETED,
            self::FAILED,
            self::EXPIRED => true,

            self::QUEUED,
            self::PROCESSING => false,
        };
    }
}
