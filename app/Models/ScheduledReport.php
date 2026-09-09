<?php

namespace App\Models;

use App\Analytics\Exports\ExportFormat;
use App\Analytics\Scheduling\ReportScheduleFrequency;
use App\Analytics\Scheduling\ScheduledReportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'saved_report_id',
    'created_by_employee_id',
    'name',
    'format',
    'frequency',
    'run_time',
    'timezone',
    'weekday',
    'day_of_month',
    'status',
    'next_run_at',
    'last_dispatched_at',
])]
final class ScheduledReport extends Model
{
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by_employee_id');
    }

    /**
     * @return HasMany<ReportExport, $this>
     */
    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'format' => ExportFormat::class,
            'frequency' => ReportScheduleFrequency::class,
            'weekday' => 'integer',
            'day_of_month' => 'integer',
            'status' => ScheduledReportStatus::class,
            'next_run_at' => 'immutable_datetime',
            'last_dispatched_at' => 'immutable_datetime',
        ];
    }
}
