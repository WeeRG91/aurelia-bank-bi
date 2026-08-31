<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Branch;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class AccountFactoryTest extends TestCase
{
    public function test_it_builds_a_coherent_synthetic_account(): void
    {
        $account = Account::factory()->make([
            'branch_id' => 1,
        ]);

        $this->assertMatchesRegularExpression(
            '/^AUR-[A-Z]{2}-\d{12}$/',
            $account->account_number,
        );
        $this->assertInstanceOf(AccountType::class, $account->account_type);
        $this->assertInstanceOf(AccountStatus::class, $account->status);
        $this->assertMatchesRegularExpression('/^[A-Z]{3}$/', $account->currency);
        $this->assertInstanceOf(CarbonImmutable::class, $account->opened_at);

        if ($account->status === AccountStatus::CLOSED) {
            $this->assertInstanceOf(CarbonImmutable::class, $account->closed_at);
            $this->assertTrue(
                $account->opened_at->lessThanOrEqualTo($account->closed_at),
            );
        } else {
            $this->assertNull($account->closed_at);
        }
    }

    public function test_account_and_branch_define_their_relationships(): void
    {
        $account = new Account;
        $branch = new Branch;

        $this->assertSame('branch_id', $account->branch()->getForeignKeyName());
        $this->assertSame('branch_id', $branch->accounts()->getForeignKeyName());
    }
}
