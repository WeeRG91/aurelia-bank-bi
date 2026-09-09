<?php

namespace App\Analytics\Auditing;

use App\Analytics\Datasets\DatasetKey;
use App\Models\AnalyticsAuditEvent;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class DatabaseAuditRecorder implements AuditRecorder
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
    ): AnalyticsAuditEvent {
        $actorId = $actor?->getKey();

        if (
            $source !== AuditSource::SYSTEM
            && $actorId === null
        ) {
            throw new InvalidArgumentException(
                'Non-system audit events require an employee actor.',
            );
        }

        if (($subjectType === null) !== ($subjectId === null)) {
            throw new InvalidArgumentException(
                'Audit subject type and identifier must be provided together.',
            );
        }

        $normalizedSubjectId = $subjectId === null
            ? null
            : trim((string) $subjectId);

        if (
            $normalizedSubjectId !== null
            && (
                $normalizedSubjectId === ''
                || mb_strlen($normalizedSubjectId) > 64
            )
        ) {
            throw new InvalidArgumentException(
                'Audit subject identifier must contain between 1 and 64 characters.',
            );
        }

        $normalizedRequestId = $this->optionalString(
            $requestId,
            64,
            'request identifier',
        );

        $normalizedUserAgent = $this->optionalString(
            $userAgent,
            512,
            'user agent',
            truncate: true,
        );

        if (
            $ipAddress !== null
            && filter_var(
                trim($ipAddress),
                FILTER_VALIDATE_IP,
            ) === false
        ) {
            throw new InvalidArgumentException(
                'Audit IP address is invalid.',
            );
        }

        return AnalyticsAuditEvent::query()->create([
            'actor_employee_id' => $actorId,
            'action' => $action,
            'outcome' => $outcome,
            'source' => $source,
            'dataset' => $dataset,
            'subject_type' => $subjectType?->value,
            'subject_id' => $normalizedSubjectId,
            'request_id' => $normalizedRequestId,
            'ip_address' => $ipAddress === null
                ? null
                : trim($ipAddress),
            'user_agent' => $normalizedUserAgent,
            'context' => ($context ?? AuditContext::from())->toArray(),
            'occurred_at' => CarbonImmutable::now('UTC'),
        ]);
    }

    private function optionalString(
        ?string $value,
        int $maximumLength,
        string $label,
        bool $truncate = false,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) <= $maximumLength) {
            return $value;
        }

        if ($truncate) {
            return mb_substr($value, 0, $maximumLength);
        }

        throw new InvalidArgumentException(
            "Audit {$label} is too long.",
        );
    }
}
