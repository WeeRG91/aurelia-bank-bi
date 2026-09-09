<?php

namespace App\Analytics\Auditing;

use App\Analytics\Datasets\DatasetKey;
use App\Models\AnalyticsAuditEvent;
use App\Models\Employee;

interface AuditRecorder
{
    public function record(
        AuditAction $action,
        AuditOutcome $outcome,
        AuditSource $source,
        ?Employee $actor = null,
        ?DatasetKey $dataset = null,
        ?AuditSubjectType $subjectType = null,
        int|string|null $subjectId = null,
        ?AuditContext $context = null,
        ?string $requestId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AnalyticsAuditEvent;
}
