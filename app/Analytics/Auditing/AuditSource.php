<?php

namespace App\Analytics\Auditing;

enum AuditSource: string
{
    case WEB = 'web';
    case QUEUE = 'queue';
    case SCHEDULER = 'scheduler';
    case SYSTEM = 'system';
}
