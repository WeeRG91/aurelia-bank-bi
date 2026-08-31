<?php

namespace Tests\Feature;

use App\Enums\LoanStatus;
use App\Enums\LoanType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class LoanFactoryTest extends TestCase
{
    public function test_it_builds_a_coherent_synthetic_loan(): void
    {
        $loan = Loan::factory()->make([
            'customer_id' => 1,
            'branch_id' => 1,
        ]);

        $this->assertMatchesRegularExpression(
            '/^LOAN-[A-Z0-9]{16}$/',
            $loan->loan_number,
        );
        $this->assertInstanceOf(LoanType::class, $loan->loan_type);
        $this->assertInstanceOf(LoanStatus::class, $loan->status);
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d{2}$/',
            $loan->principal,
        );
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d{6}$/',
            $loan->interest_rate,
        );
        $this->assertMatchesRegularExpression(
            '/^[A-Z]{3}$/',
            $loan->currency,
        );
        $this->assertGreaterThan(0, $loan->term_months);
        $this->assertInstanceOf(
            CarbonImmutable::class,
            $loan->start_date,
        );
        $this->assertInstanceOf(
            CarbonImmutable::class,
            $loan->maturity_date,
        );
        $this->assertTrue(
            $loan->maturity_date->isSameDay(
                $loan->start_date->addMonthsNoOverflow(
                    $loan->term_months,
                ),
            ),
        );
    }

    public function test_models_define_loan_relationships(): void
    {
        $loan = new Loan;
        $customer = new Customer;
        $branch = new Branch;

        $this->assertSame(
            'customer_id',
            $loan->customer()->getForeignKeyName(),
        );
        $this->assertSame(
            'branch_id',
            $loan->branch()->getForeignKeyName(),
        );
        $this->assertSame(
            'customer_id',
            $customer->loans()->getForeignKeyName(),
        );
        $this->assertSame(
            'branch_id',
            $branch->loans()->getForeignKeyName(),
        );
    }
}
