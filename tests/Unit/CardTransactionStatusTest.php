<?php

namespace Tests\Unit;

use App\Enums\CardTransactionStatus;
use PHPUnit\Framework\TestCase;

class CardTransactionStatusTest extends TestCase
{
    public function test_card_transaction_statuses_have_stable_values(): void
    {
        $this->assertSame(
            [
                'pending',
                'authorized',
                'declined',
                'settled',
                'reversed',
            ],
            array_column(CardTransactionStatus::cases(), 'value'),
        );
    }
}
