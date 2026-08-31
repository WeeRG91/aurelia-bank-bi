<?php

namespace Tests\Unit;

use App\Enums\LoanInstallmentStatus;
use PHPUnit\Framework\TestCase;

class LoanInstallmentStatusTest extends TestCase
{
    public function test_installment_statuses_have_stable_values(): void
    {
        $this->assertSame(
            ['pending', 'partially_paid', 'paid', 'overdue'],
            array_column(LoanInstallmentStatus::cases(), 'value'),
        );
    }
}
