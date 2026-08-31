<?php

namespace Tests\Feature;

use App\Enums\LoanInstallmentStatus;
use App\Models\Loan;
use App\Models\LoanInstallment;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class LoanInstallmentFactoryTest extends TestCase
{
    public function test_it_builds_a_coherent_installment(): void
    {
        $installment = LoanInstallment::factory()->make([
            'loan_id' => 1,
        ]);

        $principalDue = $this->cents($installment->principal_due);
        $interestDue = $this->cents($installment->interest_due);
        $amountPaid = $this->cents($installment->amount_paid);
        $scheduledAmount = $principalDue + $interestDue;

        $this->assertGreaterThan(0, $installment->installment_number);
        $this->assertInstanceOf(
            CarbonImmutable::class,
            $installment->due_date,
        );
        $this->assertInstanceOf(
            LoanInstallmentStatus::class,
            $installment->status,
        );
        $this->assertGreaterThan(0, $scheduledAmount);
        $this->assertGreaterThanOrEqual(0, $amountPaid);
        $this->assertLessThanOrEqual(
            $scheduledAmount,
            $amountPaid,
        );

        match ($installment->status) {
            LoanInstallmentStatus::PAID => $this->assertPaid(
                $installment,
                $scheduledAmount,
                $amountPaid,
            ),
            LoanInstallmentStatus::PARTIALLY_PAID => $this->assertPartial(
                $installment,
                $scheduledAmount,
                $amountPaid,
            ),
            default => $this->assertUnpaid(
                $installment,
                $amountPaid,
            ),
        };
    }

    public function test_loan_and_installment_define_their_relationships(): void
    {
        $installment = new LoanInstallment;
        $loan = new Loan;

        $this->assertSame(
            'loan_id',
            $installment->loan()->getForeignKeyName(),
        );
        $this->assertSame(
            'loan_id',
            $loan->installments()->getForeignKeyName(),
        );
    }

    private function assertPaid(
        LoanInstallment $installment,
        int $scheduledAmount,
        int $amountPaid,
    ): void {
        $this->assertSame($scheduledAmount, $amountPaid);
        $this->assertInstanceOf(
            CarbonImmutable::class,
            $installment->paid_at,
        );
    }

    private function assertPartial(
        LoanInstallment $installment,
        int $scheduledAmount,
        int $amountPaid,
    ): void {
        $this->assertGreaterThan(0, $amountPaid);
        $this->assertLessThan($scheduledAmount, $amountPaid);
        $this->assertNull($installment->paid_at);
    }

    private function assertUnpaid(
        LoanInstallment $installment,
        int $amountPaid,
    ): void {
        $this->assertSame(0, $amountPaid);
        $this->assertNull($installment->paid_at);
    }

    private function cents(string $amount): int
    {
        [$whole, $fraction] = explode('.', $amount);

        return ((int) $whole * 100) + (int) $fraction;
    }
}
