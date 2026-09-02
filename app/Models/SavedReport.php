<?php

namespace App\Models;

use App\Analytics\Datasets\DatasetKey;
use Database\Factories\SavedReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'owner_employee_id',
    'name',
    'description',
    'dataset',
    'definition_version',
    'definition',
])]
final class SavedReport extends Model
{
    /** @use HasFactory<SavedReportFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id');
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
        ];
    }
}
