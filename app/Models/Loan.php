<?php

namespace App\Models;

use App\Enums\LoanStatus;
use App\Enums\LoanType;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'customer_id',
    'branch_id',
    'loan_number',
    'loan_type',
    'principal',
    'currency',
    'interest_rate',
    'term_months',
    'start_date',
    'maturity_date',
    'status',
])]
class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**$
     * @return HasMany<LoanInstallment, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'loan_type' => LoanType::class,
            'principal' => 'decimal:2',
            'interest_rate' => 'decimal:6',
            'term_months' => 'integer',
            'start_date' => 'immutable_date',
            'maturity_date' => 'immutable_date',
            'status' => LoanStatus::class,
        ];
    }
}
