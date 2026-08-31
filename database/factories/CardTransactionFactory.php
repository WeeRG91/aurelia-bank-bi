<?php

namespace Database\Factories;

use App\Enums\CardStatus;
use App\Enums\CardTransactionStatus;
use App\Enums\TransactionDirection;
use App\Models\Card;
use App\Models\CardTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CardTransaction>
 */
class CardTransactionFactory extends Factory
{
    public function definition(): array
    {
        $amountInCents = fake()->numberBetween(100, 500_000);

        return [
            'card_id' => Card::factory()->state([
                'status' => CardStatus::ACTIVE,
            ]),
            'account_transaction_id' => null,
            'transaction_reference' => sprintf(
                'CTX-%s',
                fake()->unique()->regexify('[A-Z0-9]{20}'),
            ),
            'merchant_name' => fake()->company(),
            'merchant_category' => sprintf(
                '%04d',
                fake()->numberBetween(1, 9999),
            ),
            'merchant_country' => fake()->randomElement([
                'LU',
                'LU',
                'FR',
                'DE',
                'BE',
                'CH',
                'NL',
            ]),
            'amount' => sprintf(
                '%d.%02d',
                intdiv($amountInCents, 100),
                $amountInCents % 100,
            ),
            'currency' => fake()->randomElement([
                'EUR',
                'EUR',
                'EUR',
                'CHF',
                'GBP',
            ]),
            'direction' => fake()->randomElement([
                TransactionDirection::OUTGOING,
                TransactionDirection::OUTGOING,
                TransactionDirection::OUTGOING,
                TransactionDirection::INCOMING,
            ]),
            'transaction_at' => CarbonImmutable::instance(
                fake()->dateTimeBetween('-1 year', 'now'),
            )->utc()->format('Y-m-d H:i:sP'),
            'status' => fake()->randomElement([
                CardTransactionStatus::SETTLED,
                CardTransactionStatus::SETTLED,
                CardTransactionStatus::SETTLED,
                CardTransactionStatus::AUTHORIZED,
                CardTransactionStatus::PENDING,
                CardTransactionStatus::DECLINED,
                CardTransactionStatus::REVERSED,
            ]),
        ];
    }
}
