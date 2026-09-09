<?php

namespace App\Analytics\Scheduling;

enum ScheduledReportStatus: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';

    public function canDispatch(): bool
    {
        return $this === self::ACTIVE;
    }
}
