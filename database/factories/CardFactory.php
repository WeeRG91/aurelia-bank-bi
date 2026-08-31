<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\CardStatus;
use App\Enums\CardType;
use App\Enums\CustomerStatus;
use App\Models\Account;
use App\Models\Card;
use App\Models\Customer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    public function definition(): array
    {
        $issuedAt = CarbonImmutable::instance(
            fake()->dateTimeBetween('-4 years', 'now'),
        )->startOfDay();

        $expiresAt = $issuedAt
            ->addYears(4)
            ->endOfMonth()
            ->startOfDay();

        $status = $expiresAt->isPast()
            ? CardStatus::EXPIRED
            : fake()->randomElement([
                CardStatus::ACTIVE,
                CardStatus::ACTIVE,
                CardStatus::ACTIVE,
                CardStatus::BLOCKED,
                CardStatus::PENDING,
                CardStatus::CANCELLED,
            ]);

        return [
            'customer_id' => Customer::factory()->state([
                'status' => CustomerStatus::ACTIVE,
            ]),
            'account_id' => Account::factory()->state([
                'closed_at' => null,
                'status' => AccountStatus::ACTIVE,
            ]),
            'card_reference' => sprintf(
                'CRD-%s',
                fake()->unique()->regexify('[A-Z0-9]{16}'),
            ),
            'display_last_four' => sprintf(
                '%04d',
                fake()->numberBetween(0, 9999),
            ),
            'card_type' => fake()->randomElement([
                CardType::DEBIT,
                CardType::DEBIT,
                CardType::DEBIT,
                CardType::CREDIT,
                CardType::PREPAID,
            ]),
            'issued_at' => $issuedAt->format('Y-m-d'),
            'expires_at' => $expiresAt->format('Y-m-d'),
            'status' => $status,
        ];
    }
}
