<?php

namespace Tests\Unit;

use App\Enums\TransactionDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use PHPUnit\Framework\TestCase;

class TransactionEnumsTest extends TestCase
{
    public function test_transaction_directions_have_stable_values(): void
    {
        $this->assertSame(
            ['incoming', 'outgoing'],
            array_column(TransactionDirection::cases(), 'value'),
        );
    }

    public function test_transaction_statuses_have_stable_values(): void
    {
        $this->assertSame(
            ['pending', 'booked', 'failed', 'reversed'],
            array_column(TransactionStatus::cases(), 'value'),
        );
    }

    public function test_transaction_types_have_stable_values(): void
    {
        $this->assertSame(
            [
                'transfer',
                'card_payment',
                'cash_withdrawal',
                'cash_deposit',
                'direct_debit',
                'fee',
                'interest',
                'loan_payment',
            ],
            array_column(TransactionType::cases(), 'value'),
        );
    }
}
