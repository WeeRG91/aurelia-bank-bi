<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Models\Account;
use App\Models\AccountBalanceSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountBalanceSnapshot>
 */
class AccountBalanceSnapshotFactory extends Factory
{
    public function definition(): array
    {
        $snapshotDate = CarbonImmutable::instance(
            fake()->dateTimeBetween('-3 years', 'now'),
        )->startOfDay();

        $ledgerBalanceInCents = fake()->numberBetween(
            -500_000,
            50_000_000,
        );

        $availableBalanceInCents = $ledgerBalanceInCents
            + fake()->numberBetween(-200_000, 100_000);

        return [
            'account_id' => Account::factory()->state([
                'opened_at' => $snapshotDate
                    ->subYears(fake()->numberBetween(1, 10))
                    ->format('Y-m-d'),
                'closed_at' => null,
                'status' => AccountStatus::ACTIVE,
            ]),
            'snapshot_date' => $snapshotDate->format('Y-m-d'),
            'ledger_balance' => $this->decimalFromCents(
                $ledgerBalanceInCents,
            ),
            'available_balance' => $this->decimalFromCents(
                $availableBalanceInCents,
            ),
        ];
    }

    private function decimalFromCents(int $amountInCents): string
    {
        $sign = $amountInCents < 0 ? '-' : '';
        $absoluteAmount = abs($amountInCents);

        return sprintf(
            '%s%d.%02d',
            $sign,
            intdiv($absoluteAmount, 100),
            $absoluteAmount % 100,
        );
    }
}
