<?php

namespace App\Models;

use App\Enums\CardTransactionStatus;
use App\Enums\TransactionDirection;
use Database\Factories\CardTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'card_id',
    'account_transaction_id',
    'transaction_reference',
    'merchant_name',
    'merchant_category',
    'merchant_country',
    'amount',
    'currency',
    'direction',
    'transaction_at',
    'status',
])]
class CardTransaction extends Model
{
    /** @use HasFactory<CardTransactionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Card, $this>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function accountTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'account_transaction_id');
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'direction' => TransactionDirection::class,
            'transaction_at' => 'immutable_datetime',
            'status' => CardTransactionStatus::class,
        ];
    }
}
