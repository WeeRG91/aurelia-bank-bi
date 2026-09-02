<?php

namespace Database\Seeders;

use App\Enums\AccountHolderRole;
use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Enums\CustomerStatus;
use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\AccountBalanceSnapshot;
use App\Models\AccountHolder;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class AnalyticsDemoSeeder extends Seeder
{
    private const int ACCOUNTS_PER_BRANCH = 4;

    private const int TRANSACTIONS_PER_ACCOUNT = 75;

    private const int SNAPSHOT_DAYS = 90;

    public function run(): void
    {
        DB::transaction(function (): void {
            $branches = Branch::query()
                ->orderBy('id')
                ->get();

            if ($branches->isEmpty()) {
                $branches = Branch::factory()
                    ->count(3)
                    ->create();
            }

            foreach ($branches as $branch) {
                for (
                    $accountIndex = 1;
                    $accountIndex <= self::ACCOUNTS_PER_BRANCH;
                    $accountIndex++
                ) {
                    $account = $this->account(
                        $branch,
                        $accountIndex,
                    );

                    $this->primaryHolder(
                        $branch,
                        $account,
                        $accountIndex,
                    );

                    $this->transactions($account);
                    $this->balanceSnapshots($account);
                }
            }
        });

        $this->command?->info(
            'Analytics demo data seeded successfully.',
        );
    }

    private function account(
        Branch $branch,
        int $accountIndex,
    ): Account {
        $sequence = (($branch->id % 1_000_000_000) * 10)
            + $accountIndex;

        $accountNumber = sprintf(
            'AUR-%s-%012d',
            $branch->country_code,
            $sequence,
        );

        $account = Account::query()
            ->where('account_number', $accountNumber)
            ->first();

        if ($account !== null) {
            return $account;
        }

        $accountTypes = [
            AccountType::CURRENT,
            AccountType::CURRENT,
            AccountType::SAVINGS,
            AccountType::TERM_DEPOSIT,
        ];

        $currencies = [
            'EUR',
            'EUR',
            'CHF',
            'GBP',
        ];

        return Account::factory()
            ->for($branch)
            ->create([
                'account_number' => $accountNumber,
                'account_type' => $accountTypes[$accountIndex - 1],
                'currency' => $currencies[$accountIndex - 1],
                'opened_at' => CarbonImmutable::today()
                    ->subYears(5)
                    ->subMonths($accountIndex)
                    ->format('Y-m-d'),
                'closed_at' => null,
                'status' => AccountStatus::ACTIVE,
            ]);
    }

    private function primaryHolder(
        Branch $branch,
        Account $account,
        int $accountIndex,
    ): void {
        $hasPrimaryHolder = $account->accountHolders()
            ->where('role', AccountHolderRole::PRIMARY->value)
            ->whereNull('ended_at')
            ->exists();

        if ($hasPrimaryHolder) {
            return;
        }

        $sequence = 80_000_000
            + ((($branch->id % 100_000) * 10) + $accountIndex);

        $customerNumber = sprintf(
            'CUS-%08d',
            $sequence,
        );

        $customer = Customer::query()
            ->where('customer_number', $customerNumber)
            ->first();

        if ($customer === null) {
            $customer = Customer::factory()->create([
                'customer_number' => $customerNumber,
                'birth_date' => CarbonImmutable::today()
                    ->subYears(30 + $accountIndex)
                    ->format('Y-m-d'),
                'nationality' => $branch->country_code,
                'country_of_residence' => $branch->country_code,
                'city' => $branch->city,
                'postal_code' => 'DEMO',
                'annual_income' => '75000.00',
                'annual_income_currency' => 'EUR',
                'joined_at' => $account->opened_at->format('Y-m-d'),
                'status' => CustomerStatus::ACTIVE,
            ]);
        }

        AccountHolder::factory()
            ->for($account)
            ->for($customer)
            ->create([
                'role' => AccountHolderRole::PRIMARY,
                'started_at' => $account->opened_at->format('Y-m-d'),
                'ended_at' => null,
            ]);
    }

    private function transactions(Account $account): void
    {
        for (
            $index = 1;
            $index <= self::TRANSACTIONS_PER_ACCOUNT;
            $index++
        ) {
            $sequence = (($account->id % 1_000_000_000_000) * 100)
                + $index;

            $reference = sprintf(
                'TXN-D%019d',
                $sequence,
            );

            if (
                Transaction::query()
                    ->where('transaction_reference', $reference)
                    ->exists()
            ) {
                continue;
            }

            $bookedAt = CarbonImmutable::now('UTC')
                ->subDays(($index - 1) % 90)
                ->setTime(
                    8 + ($index % 10),
                    ($index * 7) % 60,
                );

            Transaction::factory()
                ->for($account)
                ->create([
                    'transaction_reference' => $reference,
                    'currency' => $account->currency,
                    'booked_at' => $bookedAt->format('Y-m-d H:i:sP'),
                    'value_date' => $bookedAt
                        ->subDays($index % 3)
                        ->format('Y-m-d'),
                    'status' => TransactionStatus::BOOKED,
                    'reversal_of_transaction_id' => null,
                ]);
        }
    }

    private function balanceSnapshots(Account $account): void
    {
        $balanceInCents = 500_000 + ($account->id * 10_000);

        for (
            $daysAgo = self::SNAPSHOT_DAYS - 1;
            $daysAgo >= 0;
            $daysAgo--
        ) {
            $snapshotDate = CarbonImmutable::today()
                ->subDays($daysAgo);

            $dailyMovement = (
                (($daysAgo * 7_919) + ($account->id * 104_729))
                % 200_001
            ) - 100_000;

            $balanceInCents += $dailyMovement;

            $reservedInCents = (
                ($daysAgo * 997) + ($account->id * 101)
            ) % 50_001;

            AccountBalanceSnapshot::query()->firstOrCreate(
                [
                    'account_id' => $account->id,
                    'snapshot_date' => $snapshotDate->format('Y-m-d'),
                ],
                [
                    'ledger_balance' => $this->decimalFromCents(
                        $balanceInCents,
                    ),
                    'available_balance' => $this->decimalFromCents(
                        $balanceInCents - $reservedInCents,
                    ),
                ],
            );
        }
    }

    private function decimalFromCents(
        int $amountInCents,
    ): string {
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
