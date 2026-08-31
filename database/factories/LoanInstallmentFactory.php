<?php

namespace Database\Factories;

use App\Enums\LoanInstallmentStatus;
use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\LoanInstallment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanInstallment>
 */
class LoanInstallmentFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement([
            LoanInstallmentStatus::PAID,
            LoanInstallmentStatus::PAID,
            LoanInstallmentStatus::PAID,
            LoanInstallmentStatus::PENDING,
            LoanInstallmentStatus::PARTIALLY_PAID,
            LoanInstallmentStatus::OVERDUE,
        ]);

        $dueDate = match ($status) {
            LoanInstallmentStatus::PENDING => CarbonImmutable::instance(
                fake()->dateTimeBetween('+1 day', '+1 year'),
            )->startOfDay(),

            default => CarbonImmutable::instance(
                fake()->dateTimeBetween('-2 years', 'now'),
            )->startOfDay(),
        };

        $principalDueInCents = fake()->numberBetween(
            5_000,
            200_000,
        );

        $interestDueInCents = fake()->numberBetween(
            500,
            50_000,
        );

        $scheduledAmountInCents = $principalDueInCents
            + $interestDueInCents;

        $amountPaidInCents = match ($status) {
            LoanInstallmentStatus::PAID => $scheduledAmountInCents,

            LoanInstallmentStatus::PARTIALLY_PAID => fake()->numberBetween(
                1,
                $scheduledAmountInCents - 1,
            ),

            default => 0,
        };

        $paidAt = $status === LoanInstallmentStatus::PAID
            ? CarbonImmutable::instance(
                fake()->dateTimeBetween(
                    $dueDate->subDays(30),
                    min(
                        $dueDate->addDays(60),
                        CarbonImmutable::now(),
                    ),
                ),
            )->utc()
            : null;

        return [
            'loan_id' => Loan::factory()->state([
                'status' => LoanStatus::ACTIVE,
            ]),
            'installment_number' => fake()->numberBetween(1, 600),
            'due_date' => $dueDate->format('Y-m-d'),
            'principal_due' => $this->decimalFromCents(
                $principalDueInCents,
            ),
            'interest_due' => $this->decimalFromCents(
                $interestDueInCents,
            ),
            'amount_paid' => $this->decimalFromCents(
                $amountPaidInCents,
            ),
            'paid_at' => $paidAt?->format('Y-m-d H:i:sP'),
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
}
