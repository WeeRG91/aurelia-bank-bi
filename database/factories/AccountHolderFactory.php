<?php

namespace Database\Factories;

use App\Enums\AccountHolderRole;
use App\Enums\AccountStatus;
use App\Models\Account;
use App\Models\AccountHolder;
use App\Models\Customer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountHolder>
 */
class AccountHolderFactory extends Factory
{
    public function definition(): array
    {
        $startedAt = CarbonImmutable::today();

        return [
            'account_id' => Account::factory()->state([
                'opened_at' => $startedAt->format('Y-m-d'),
                'closed_at' => null,
                'status' => AccountStatus::ACTIVE,
            ]),
            'customer_id' => Customer::factory()->state([
                'joined_at' => $startedAt->format('Y-m-d'),
            ]),
            'role' => AccountHolderRole::PRIMARY,
            'started_at' => $startedAt->format('Y-m-d'),
            'ended_at' => null,
        ];
    }

    public function joint(): static
    {
        return $this->state(fn (): array => [
            'role' => AccountHolderRole::JOINT,
        ]);
    }
}
