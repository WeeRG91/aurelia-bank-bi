<?php

namespace Tests\Feature;

use App\Enums\CardTransactionStatus;
use App\Enums\TransactionDirection;
use App\Models\Card;
use App\Models\CardTransaction;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class CardTransactionFactoryTest extends TestCase
{
    public function test_it_builds_a_safe_synthetic_card_transaction(): void
    {
        $cardTransaction = CardTransaction::factory()->make([
            'card_id' => 1,
        ]);

        $this->assertNull(
            $cardTransaction->account_transaction_id,
        );
        $this->assertMatchesRegularExpression(
            '/^CTX-[A-Z0-9]{20}$/',
            $cardTransaction->transaction_reference,
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}$/',
            $cardTransaction->merchant_category,
        );
        $this->assertMatchesRegularExpression(
            '/^[A-Z]{2}$/',
            $cardTransaction->merchant_country,
        );
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d{2}$/',
            $cardTransaction->amount,
        );
        $this->assertInstanceOf(
            TransactionDirection::class,
            $cardTransaction->direction,
        );
        $this->assertInstanceOf(
            CardTransactionStatus::class,
            $cardTransaction->status,
        );
        $this->assertInstanceOf(
            CarbonImmutable::class,
            $cardTransaction->transaction_at,
        );
    }

    public function test_models_define_card_transaction_relationships(): void
    {
        $cardTransaction = new CardTransaction;
        $card = new Card;
        $transaction = new Transaction;

        $this->assertSame(
            'card_id',
            $cardTransaction->card()->getForeignKeyName(),
        );
        $this->assertSame(
            'account_transaction_id',
            $cardTransaction
                ->accountTransaction()
                ->getForeignKeyName(),
        );
        $this->assertSame(
            'card_id',
            $card->cardTransactions()->getForeignKeyName(),
        );
        $this->assertSame(
            'account_transaction_id',
            $transaction
                ->cardTransaction()
                ->getForeignKeyName(),
        );
    }
}
