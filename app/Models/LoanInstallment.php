<?php

namespace App\Models;

use App\Enums\LoanInstallmentStatus;
use Database\Factories\LoanInstallmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'loan_id',
    'installment_number',
    'due_date',
    'principal_due',
    'interest_due',
    'amount_paid',
    'paid_at',
    'status',
])]
class LoanInstallment extends Model
{
    /** @use HasFactory<LoanInstallmentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Loan, $this>
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    protected function casts(): array
    {
        return [
            'installment_number' => 'integer',
            'due_date' => 'immutable_date',
            'principal_due' => 'decimal:2',
            'interest_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'paid_at' => 'immutable_datetime',
            'status' => LoanInstallmentStatus::class,
        ];
    }
}
