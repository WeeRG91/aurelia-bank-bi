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
use Illuminate\Database\Eloquent\Relations\HasOne;

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
    'reversal_of_transaction_id',
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
     * @return BelongsTo<Transaction, $this>
     */
    public function originalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'reversal_of_transaction_id');
    }

    /**
     * @return HasOne<HasOne, $this>
     */
    public function reversalTransaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'reversal_of_transaction_id');
    }

    /**
     * @return HasOne<CardTransaction, $this>
     */
    public function cardTransaction(): HasOne
    {
        return $this->hasOne(CardTransaction::class, 'account_transaction_id');
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
