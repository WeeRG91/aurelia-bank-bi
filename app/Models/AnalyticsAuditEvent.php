<?php

namespace App\Models;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSource;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Datasets\DatasetKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'actor_employee_id',
    'action',
    'outcome',
    'source',
    'dataset',
    'subject_type',
    'subject_id',
    'request_id',
    'ip_address',
    'user_agent',
    'context',
    'occurred_at',
])]
final class AnalyticsAuditEvent extends Model
{
    use HasUlids;

    public $timestamps = false;

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'actor_employee_id');
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'outcome' => AuditOutcome::class,
            'source' => AuditSource::class,
            'dataset' => DatasetKey::class,
            'context' => 'array',
            'occurred_at' => 'immutable_datetime',
            'subject_type' => AuditSubjectType::class,
        ];
    }
}
