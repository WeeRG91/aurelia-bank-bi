<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Branch;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement([
            AccountStatus::PENDING,
            AccountStatus::ACTIVE,
            AccountStatus::ACTIVE,
            AccountStatus::ACTIVE,
            AccountStatus::FROZEN,
            AccountStatus::DORMANT,
            AccountStatus::CLOSED,
        ]);

        $openedAt = CarbonImmutable::instance(
            $status === AccountStatus::PENDING
                ? fake()->dateTimeBetween('now', '+60 days')
                : fake()->dateTimeBetween('-20 years', 'now'),
        )->startOfDay();

        $closedAt = $status === AccountStatus::CLOSED
            ? CarbonImmutable::instance(
                fake()->dateTimeBetween($openedAt, 'now'),
            )->startOfDay()
            : null;

        return [
            'branch_id' => Branch::factory(),
            'account_number' => sprintf(
                'AUR-LU-%012d',
                fake()->unique()->numberBetween(1, 999_999_999_999),
            ),
            'account_type' => fake()->randomElement([
                AccountType::CURRENT,
                AccountType::CURRENT,
                AccountType::SAVINGS,
                AccountType::SAVINGS,
                AccountType::TERM_DEPOSIT,
            ]),
            'currency' => fake()->randomElement([
                'EUR',
                'EUR',
                'EUR',
                'CHF',
                'GBP',
            ]),
            'opened_at' => $openedAt->format('Y-m-d'),
            'closed_at' => $closedAt?->format('Y-m-d'),
            'status' => $status,
        ];
    }
}
