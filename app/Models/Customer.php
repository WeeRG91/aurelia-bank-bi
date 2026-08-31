<?php

namespace App\Models;

use App\Enums\CustomerSegment;
use App\Enums\CustomerStatus;
use App\Enums\RiskLevel;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'customer_number',
    'first_name',
    'last_name',
    'birth_date',
    'email',
    'phone',
    'nationality',
    'country_of_residence',
    'city',
    'postal_code',
    'occupation',
    'annual_income',
    'annual_income_currency',
    'customer_segment',
    'risk_level',
    'joined_at',
    'status',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * @return HasMany<AccountHolder, $this>
     */
    public function accountHolders(): HasMany
    {
        return $this->hasMany(AccountHolder::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'immutable_date',
            'annual_income' => 'decimal:2',
            'customer_segment' => CustomerSegment::class,
            'risk_level' => RiskLevel::class,
            'joined_at' => 'immutable_date',
            'status' => CustomerStatus::class,
        ];
    }
}
