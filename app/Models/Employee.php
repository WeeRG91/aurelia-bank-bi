<?php

namespace App\Models;

use App\Enums\EmployeeDepartment;
use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'branch_id',
    'employee_number',
    'department',
    'job_title',
    'role',
    'hired_at',
    'terminated_at',
    'status',
])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<SavedReport, $this>
     */
    public function savedReports(): HasMany
    {
        return $this->hasMany(SavedReport::class, 'owner_employee_id');
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'department' => EmployeeDepartment::class,
            'role' => EmployeeRole::class,
            'hired_at' => 'immutable_date',
            'terminated_at' => 'immutable_date',
            'status' => EmployeeStatus::class,
        ];
    }
}
