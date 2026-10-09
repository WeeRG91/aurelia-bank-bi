<?php

namespace App\Analytics\Warehouse\Etl;

enum EtlRunStatus: string
{
    case RUNNING = 'running';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::RUNNING => false,
            self::SUCCEEDED,
            self::FAILED => true,
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::RUNNING => in_array(
                $next,
                [
                    self::SUCCEEDED,
                    self::FAILED,
                ],
                true,
            ),
            self::SUCCEEDED,
            self::FAILED => false,
        };
    }
}
