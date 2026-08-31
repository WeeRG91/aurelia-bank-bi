<?php

namespace Tests\Unit;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use PHPUnit\Framework\TestCase;

class AccountEnumsTest extends TestCase
{
    public function test_account_types_have_stable_values(): void
    {
        $this->assertSame(
            ['current', 'savings', 'term_deposit'],
            array_column(AccountType::cases(), 'value'),
        );
    }

    public function test_account_statuses_have_stable_values(): void
    {
        $this->assertSame(
            ['pending', 'active', 'frozen', 'dormant', 'closed'],
            array_column(AccountStatus::cases(), 'value'),
        );
    }
}
