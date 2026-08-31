<?php

namespace App\Models;

use Database\Factories\AccountBalanceSnapshotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'account_id',
    'snapshot_date',
    'ledger_balance',
    'available_balance',
])]
class AccountBalanceSnapshot extends Model
{
    /** @use HasFactory<AccountBalanceSnapshotFactory> */
    use HasFactory;

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
            'snapshot_date' => 'immutable_date',
            'ledger_balance' => 'decimal:2',
            'available_balance' => 'decimal:2',
        ];
    }
}
