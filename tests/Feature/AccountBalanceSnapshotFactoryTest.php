<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountBalanceSnapshot;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class AccountBalanceSnapshotFactoryTest extends TestCase
{
    public function test_it_builds_a_coherent_balance_snapshot(): void
    {
        $snapshot = AccountBalanceSnapshot::factory()->make([
            'account_id' => 1,
        ]);

        $this->assertInstanceOf(
            CarbonImmutable::class,
            $snapshot->snapshot_date,
        );
        $this->assertMatchesRegularExpression(
            '/^-?\d+\.\d{2}$/',
            $snapshot->ledger_balance,
        );
        $this->assertMatchesRegularExpression(
            '/^-?\d+\.\d{2}$/',
            $snapshot->available_balance,
        );
    }

    public function test_snapshot_and_account_define_their_relationships(): void
    {
        $snapshot = new AccountBalanceSnapshot;
        $account = new Account;

        $this->assertSame(
            'account_id',
            $snapshot->account()->getForeignKeyName(),
        );
        $this->assertSame(
            'account_id',
            $account->balanceSnapshots()->getForeignKeyName(),
        );
    }
}
