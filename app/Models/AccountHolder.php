<?php

namespace App\Models;

use App\Enums\AccountHolderRole;
use Database\Factories\AccountHolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'account_id',
    'customer_id',
    'role',
    'started_at',
    'ended_at',
])]
class AccountHolder extends Model
{
    /** @use HasFactory<AccountHolderFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => AccountHolderRole::class,
            'started_at' => 'immutable_date',
            'ended_at' => 'immutable_date',
        ];
    }
}
