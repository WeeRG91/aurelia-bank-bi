<?php

namespace Tests\Unit;

use App\Enums\CardStatus;
use App\Enums\CardType;
use PHPUnit\Framework\TestCase;

class CardEnumsTest extends TestCase
{
    public function test_card_types_have_stable_values(): void
    {
        $this->assertSame(
            ['debit', 'credit', 'prepaid'],
            array_column(CardType::cases(), 'value'),
        );
    }

    public function test_card_statuses_have_stable_values(): void
    {
        $this->assertSame(
            ['pending', 'active', 'blocked', 'expired', 'cancelled'],
            array_column(CardStatus::cases(), 'value'),
        );
    }
}
