<?php

namespace App\Analytics\Auditing;

use App\Analytics\Datasets\DatasetKey;
use App\Http\Middleware\AssignRequestId;
use App\Models\AnalyticsAuditEvent;
use App\Models\Employee;
use Illuminate\Http\Request;

final readonly class WebAuditRecorder
{
    public function __construct(
        private AuditRecorder $recorder,
    ) {}

    public function record(
        Request $request,
        AuditAction $action,
        AuditOutcome $outcome,
        Employee $actor,
        ?DatasetKey $dataset = null,
        ?AuditSubjectType $subjectType = null,
        int|string|null $subjectId = null,
        ?AuditContext $context = null,
    ): AnalyticsAuditEvent {
        $requestId = $request->attributes->get(
            AssignRequestId::ATTRIBUTE,
        );

        return $this->recorder->record(
            action: $action,
            outcome: $outcome,
            source: AuditSource::WEB,
            actor: $actor,
            dataset: $dataset,
            subjectType: $subjectType,
            subjectId: $subjectId,
            context: $context,
            requestId: is_string($requestId)
                ? $requestId
                : null,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );
    }
}
