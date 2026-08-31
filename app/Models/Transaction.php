<?php

namespace App\Models;

use App\Enums\TransactionDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'account_id',
    'transaction_reference',
    'transaction_type',
    'category',
    'amount',
    'currency',
    'direction',
    'merchant_name',
    'counterparty_account',
    'booked_at',
    'value_date',
    'status',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Account, string>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction_type' => TransactionType::class,
            'amount' => 'decimal:2',
            'direction' => TransactionDirection::class,
            'booked_at' => 'immutable_datetime',
            'value_date' => 'immutable_date',
            'status' => TransactionStatus::class,
        ];
    }
}
