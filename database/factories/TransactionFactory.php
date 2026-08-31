<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\TransactionDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $activity = fake()->randomElement([
            [
                'type' => TransactionType::TRANSFER,
                'category' => 'salary',
                'direction' => TransactionDirection::INCOMING,
                'merchant' => false,
                'counterparty' => true,
            ],
            [
                'type' => TransactionType::TRANSFER,
                'category' => 'bank_transfer',
                'direction' => TransactionDirection::OUTGOING,
                'merchant' => false,
                'counterparty' => true,
            ],
            [
                'type' => TransactionType::CARD_PAYMENT,
                'category' => 'groceries',
                'direction' => TransactionDirection::OUTGOING,
                'merchant' => true,
                'counterparty' => false,
            ],
            [
                'type' => TransactionType::CASH_WITHDRAWAL,
                'category' => 'cash',
                'direction' => TransactionDirection::OUTGOING,
                'merchant' => false,
                'counterparty' => false,
            ],
            [
                'type' => TransactionType::CASH_DEPOSIT,
                'category' => 'cash',
                'direction' => TransactionDirection::INCOMING,
                'merchant' => false,
                'counterparty' => false,
            ],
            [
                'type' => TransactionType::DIRECT_DEBIT,
                'category' => 'utilities',
                'direction' => TransactionDirection::OUTGOING,
                'merchant' => false,
                'counterparty' => true,
            ],
            [
                'type' => TransactionType::FEE,
                'category' => 'bank_fees',
                'direction' => TransactionDirection::OUTGOING,
                'merchant' => false,
                'counterparty' => false,
            ],
            [
                'type' => TransactionType::INTEREST,
                'category' => 'interest_income',
                'direction' => TransactionDirection::INCOMING,
                'merchant' => false,
                'counterparty' => false,
            ],
            [
                'type' => TransactionType::LOAN_PAYMENT,
                'category' => 'loan_repayment',
                'direction' => TransactionDirection::OUTGOING,
                'merchant' => false,
                'counterparty' => true,
            ],
        ]);

        $status = fake()->randomElement([
            TransactionStatus::BOOKED,
            TransactionStatus::BOOKED,
            TransactionStatus::BOOKED,
            TransactionStatus::BOOKED,
            TransactionStatus::PENDING,
            TransactionStatus::FAILED,
            TransactionStatus::REVERSED,
        ]);

        $bookedAt = in_array(
            $status,
            [TransactionStatus::BOOKED, TransactionStatus::REVERSED],
            true,
        )
            ? CarbonImmutable::instance(
                fake()->dateTimeBetween('-3 years', 'now'),
            )->utc()
            : null;

        $valueDate = $bookedAt?->subDays(
            fake()->numberBetween(0, 2),
        );

        $amountInCents = fake()->numberBetween(100, 2_500_000);

        return [
            'account_id' => Account::factory()->state([
                'opened_at' => CarbonImmutable::today()
                    ->subYears(5)
                    ->format('Y-m-d'),
                'closed_at' => null,
                'status' => AccountStatus::ACTIVE,
            ]),
            'transaction_reference' => sprintf(
                'TXN-%s',
                fake()->unique()->regexify('[A-Z0-9]{20}'),
            ),
            'transaction_type' => $activity['type'],
            'category' => $activity['category'],
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
            'direction' => $activity['direction'],
            'merchant_name' => $activity['merchant']
                ? fake()->company()
                : null,
            'counterparty_account' => $activity['counterparty']
                ? sprintf(
                    'SYN-CPY-%012d',
                    fake()->numberBetween(1, 999_999_999_999),
                )
                : null,
            'booked_at' => $bookedAt?->format('Y-m-d H:i:sP'),
            'value_date' => $valueDate?->format('Y-m-d'),
            'status' => $status,
        ];
    }
}
