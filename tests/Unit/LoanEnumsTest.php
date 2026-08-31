<?php

namespace Tests\Unit;

use App\Enums\LoanStatus;
use App\Enums\LoanType;
use PHPUnit\Framework\TestCase;

class LoanEnumsTest extends TestCase
{
    public function test_loan_types_have_stable_values(): void
    {
        $this->assertSame(
            ['personal', 'mortgage', 'auto', 'business'],
            array_column(LoanType::cases(), 'value'),
        );
    }

    public function test_loan_statuses_have_stable_values(): void
    {
        $this->assertSame(
            ['pending', 'active', 'paid', 'defaulted', 'cancelled'],
            array_column(LoanStatus::cases(), 'value'),
        );
    }
}
