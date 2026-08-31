<?php

namespace App\Models;

use App\Enums\CardStatus;
use App\Enums\CardType;
use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id',
    'account_id',
    'card_reference',
    'display_last_four',
    'card_type',
    'issued_at',
    'expires_at',
    'status',
])]
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Account, $this>
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
            'card_type' => CardType::class,
            'issued_at' => 'immutable_date',
            'expires_at' => 'immutable_date',
            'status' => CardStatus::class,
        ];
    }
}
