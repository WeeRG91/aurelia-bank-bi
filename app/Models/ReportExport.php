<?php

namespace App\Models;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Exports\ExportFormat;
use App\Analytics\Exports\ReportExportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'saved_report_id',
    'requested_by_employee_id',
    'dataset',
    'definition_version',
    'definition',
    'format',
    'status',
    'disk',
    'path',
    'filename',
    'row_count',
    'file_size_bytes',
    'failure_message',
    'started_at',
    'finished_at',
    'expires_at',
    'scheduled_report_id',
])]
class ReportExport extends Model
{
    use HasUlids;

    /**
     * @return BelongsTo<SavedReport, $this>
     */
    public function savedReport(): BelongsTo
    {
        return $this->belongsTo(SavedReport::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by_employee_id');
    }

    /**
     * @return BelongsTo<ScheduledReport, $this>
     */
    public function scheduledReport(): BelongsTo
    {
        return $this->belongsTo(ScheduledReport::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dataset' => DatasetKey::class,
            'definition_version' => 'integer',
            'definition' => 'array',
            'format' => ExportFormat::class,
            'status' => ReportExportStatus::class,
            'row_count' => 'integer',
            'file_size_bytes' => 'integer',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
