<?php

namespace Tests\Feature;

use App\Enums\TransactionDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class TransactionFactoryTest extends TestCase
{
    public function test_it_builds_a_coherent_synthetic_transaction(): void
    {
        $transaction = Transaction::factory()->make([
            'account_id' => 1,
        ]);

        $this->assertMatchesRegularExpression(
            '/^TXN-[A-Z0-9]{20}$/',
            $transaction->transaction_reference,
        );
        $this->assertInstanceOf(
            TransactionType::class,
            $transaction->transaction_type,
        );
        $this->assertInstanceOf(
            TransactionDirection::class,
            $transaction->direction,
        );
        $this->assertInstanceOf(
            TransactionStatus::class,
            $transaction->status,
        );
        $this->assertMatchesRegularExpression(
            '/^[a-z][a-z0-9_]*$/',
            $transaction->category,
        );
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d{2}$/',
            $transaction->amount,
        );
        $this->assertGreaterThan(
            0,
            (int) str_replace('.', '', $transaction->amount),
        );
        $this->assertMatchesRegularExpression(
            '/^[A-Z]{3}$/',
            $transaction->currency,
        );

        if (in_array(
            $transaction->status,
            [TransactionStatus::BOOKED, TransactionStatus::REVERSED],
            true,
        )) {
            $this->assertInstanceOf(
                CarbonImmutable::class,
                $transaction->booked_at,
            );
            $this->assertInstanceOf(
                CarbonImmutable::class,
                $transaction->value_date,
            );
        } else {
            $this->assertNull($transaction->booked_at);
            $this->assertNull($transaction->value_date);
        }
    }

    public function test_transaction_and_account_define_their_relationships(): void
    {
        $transaction = new Transaction;
        $account = new Account;

        $this->assertSame(
            'account_id',
            $transaction->account()->getForeignKeyName(),
        );
        $this->assertSame(
            'account_id',
            $account->transactions()->getForeignKeyName(),
        );
    }
}
