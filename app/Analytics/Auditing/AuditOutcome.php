<?php

namespace App\Analytics\Auditing;

enum AuditOutcome: string
{
    case SUCCEEDED = 'succeeded';
    case DENIED = 'denied';
    case FAILED = 'failed';
}
