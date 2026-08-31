<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'branch_id',
    'account_number',
    'account_type',
    'currency',
    'opened_at',
    'closed_at',
    'status',
])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<AccountHolder, $this>
     */
    public function accountHolders(): HasMany
    {
        return $this->hasMany(AccountHolder::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<AccountBalanceSnapshot, $this>
     */
    public function balanceSnapshots(): HasMany
    {
        return $this->hasMany(AccountBalanceSnapshot::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'account_type' => AccountType::class,
            'opened_at' => 'immutable_date',
            'closed_at' => 'immutable_date',
            'status' => AccountStatus::class,
        ];
    }
}
