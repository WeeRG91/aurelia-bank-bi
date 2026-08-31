<?php

namespace Database\Factories;

use App\Enums\CustomerSegment;
use App\Enums\CustomerStatus;
use App\Enums\LoanStatus;
use App\Enums\LoanType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    public function definition(): array
    {
        $profile = fake()->randomElement([
            [
                'type' => LoanType::PERSONAL,
                'principal_min' => 500_000,
                'principal_max' => 5_000_000,
                'rate_min' => 2_500_000,
                'rate_max' => 14_000_000,
                'terms' => [12, 24, 36, 48, 60, 72, 84],
            ],
            [
                'type' => LoanType::MORTGAGE,
                'principal_min' => 10_000_000,
                'principal_max' => 100_000_000,
                'rate_min' => 1_500_000,
                'rate_max' => 7_000_000,
                'terms' => [120, 180, 240, 300, 360],
            ],
            [
                'type' => LoanType::AUTO,
                'principal_min' => 1_000_000,
                'principal_max' => 10_000_000,
                'rate_min' => 2_000_000,
                'rate_max' => 12_000_000,
                'terms' => [24, 36, 48, 60, 72, 84],
            ],
            [
                'type' => LoanType::BUSINESS,
                'principal_min' => 2_000_000,
                'principal_max' => 200_000_000,
                'rate_min' => 2_000_000,
                'rate_max' => 15_000_000,
                'terms' => [12, 24, 36, 60, 84, 120, 180],
            ],
        ]);

        $termMonths = fake()->randomElement($profile['terms']);

        $status = fake()->randomElement([
            LoanStatus::ACTIVE,
            LoanStatus::ACTIVE,
            LoanStatus::ACTIVE,
            LoanStatus::PAID,
            LoanStatus::DEFAULTED,
            LoanStatus::PENDING,
            LoanStatus::CANCELLED,
        ]);

        $startDate = match ($status) {
            LoanStatus::PENDING => CarbonImmutable::instance(
                fake()->dateTimeBetween('now', '+90 days'),
            )->startOfDay(),

            LoanStatus::ACTIVE => CarbonImmutable::today()->subMonths(
                fake()->numberBetween(0, max(0, $termMonths - 1)),
            ),

            LoanStatus::DEFAULTED => CarbonImmutable::instance(
                fake()->dateTimeBetween('-10 years', '-3 months'),
            )->startOfDay(),

            LoanStatus::PAID => CarbonImmutable::instance(
                fake()->dateTimeBetween('-10 years', '-1 month'),
            )->startOfDay(),

            LoanStatus::CANCELLED => CarbonImmutable::instance(
                fake()->dateTimeBetween('-2 years', '+3 months'),
            )->startOfDay(),
        };

        $maturityDate = $startDate->addMonthsNoOverflow($termMonths);

        $customerState = [
            'status' => CustomerStatus::ACTIVE,
        ];

        if ($profile['type'] === LoanType::BUSINESS) {
            $customerState['customer_segment'] = CustomerSegment::BUSINESS;
        }

        $principalInCents = fake()->numberBetween(
            $profile['principal_min'],
            $profile['principal_max'],
        );

        $interestRateInMillionths = fake()->numberBetween(
            $profile['rate_min'],
            $profile['rate_max'],
        );

        return [
            'customer_id' => Customer::factory()->state($customerState),
            'branch_id' => Branch::factory(),
            'loan_number' => sprintf(
                'LOAN-%s',
                fake()->unique()->regexify('[A-Z0-9]{16}'),
            ),
            'loan_type' => $profile['type'],
            'principal' => $this->decimalFromCents(
                $principalInCents,
            ),
            'currency' => fake()->randomElement([
                'EUR',
                'EUR',
                'EUR',
                'CHF',
                'GBP',
            ]),
            'interest_rate' => $this->percentageFromMillionths(
                $interestRateInMillionths,
            ),
            'term_months' => $termMonths,
            'start_date' => $startDate->format('Y-m-d'),
            'maturity_date' => $maturityDate->format('Y-m-d'),
            'status' => $status,
        ];
    }

    private function decimalFromCents(int $amountInCents): string
    {
        return sprintf(
            '%d.%02d',
            intdiv($amountInCents, 100),
            $amountInCents % 100,
        );
    }

    private function percentageFromMillionths(
        int $percentageInMillionths,
    ): string {
        return sprintf(
            '%d.%06d',
            intdiv($percentageInMillionths, 1_000_000),
            $percentageInMillionths % 1_000_000,
        );
    }
}
