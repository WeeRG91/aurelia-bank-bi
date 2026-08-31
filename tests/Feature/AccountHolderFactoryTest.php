<?php

namespace Tests\Feature;

use App\Enums\AccountHolderRole;
use App\Models\Account;
use App\Models\AccountHolder;
use App\Models\Customer;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class AccountHolderFactoryTest extends TestCase
{
    public function test_it_builds_a_coherent_account_holder(): void
    {
        $accountHolder = AccountHolder::factory()->make([
            'account_id' => 1,
            'customer_id' => 1,
        ]);

        $this->assertInstanceOf(
            AccountHolderRole::class,
            $accountHolder->role,
        );
        $this->assertSame(
            AccountHolderRole::PRIMARY,
            $accountHolder->role,
        );
        $this->assertInstanceOf(
            CarbonImmutable::class,
            $accountHolder->started_at,
        );
        $this->assertNull($accountHolder->ended_at);
    }

    public function test_it_can_build_a_joint_holder(): void
    {
        $accountHolder = AccountHolder::factory()
            ->joint()
            ->make([
                'account_id' => 1,
                'customer_id' => 1,
            ]);

        $this->assertSame(
            AccountHolderRole::JOINT,
            $accountHolder->role,
        );
    }

    public function test_models_define_account_holder_relationships(): void
    {
        $accountHolder = new AccountHolder;
        $account = new Account;
        $customer = new Customer;

        $this->assertSame(
            'account_id',
            $accountHolder->account()->getForeignKeyName(),
        );
        $this->assertSame(
            'customer_id',
            $accountHolder->customer()->getForeignKeyName(),
        );
        $this->assertSame(
            'account_id',
            $account->accountHolders()->getForeignKeyName(),
        );
        $this->assertSame(
            'customer_id',
            $customer->accountHolders()->getForeignKeyName(),
        );
    }
}
