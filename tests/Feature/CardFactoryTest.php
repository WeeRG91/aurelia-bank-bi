<?php

namespace Tests\Feature;

use App\Enums\CardStatus;
use App\Enums\CardType;
use App\Models\Account;
use App\Models\Card;
use App\Models\Customer;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class CardFactoryTest extends TestCase
{
    public function test_it_builds_a_safe_coherent_synthetic_card(): void
    {
        $card = Card::factory()->make([
            'customer_id' => 1,
            'account_id' => 1,
        ]);

        $this->assertMatchesRegularExpression(
            '/^CRD-[A-Z0-9]{16}$/',
            $card->card_reference,
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}$/',
            $card->display_last_four,
        );
        $this->assertInstanceOf(CardType::class, $card->card_type);
        $this->assertInstanceOf(CardStatus::class, $card->status);
        $this->assertInstanceOf(
            CarbonImmutable::class,
            $card->issued_at,
        );
        $this->assertInstanceOf(
            CarbonImmutable::class,
            $card->expires_at,
        );
        $this->assertTrue(
            $card->issued_at->lessThan($card->expires_at),
        );

        if ($card->expires_at->isPast()) {
            $this->assertSame(CardStatus::EXPIRED, $card->status);
        }
    }

    public function test_card_relationships_use_expected_foreign_keys(): void
    {
        $card = new Card;
        $customer = new Customer;
        $account = new Account;

        $this->assertSame(
            'customer_id',
            $card->customer()->getForeignKeyName(),
        );
        $this->assertSame(
            'account_id',
            $card->account()->getForeignKeyName(),
        );
        $this->assertSame(
            'customer_id',
            $customer->cards()->getForeignKeyName(),
        );
        $this->assertSame(
            'account_id',
            $account->cards()->getForeignKeyName(),
        );
    }
}
