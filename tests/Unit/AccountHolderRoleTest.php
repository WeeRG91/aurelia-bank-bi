<?php

namespace Tests\Unit;

use App\Enums\AccountHolderRole;
use PHPUnit\Framework\TestCase;

class AccountHolderRoleTest extends TestCase
{
    public function test_account_holder_roles_have_stable_values(): void
    {
        $this->assertSame(
            ['primary', 'joint'],
            array_column(AccountHolderRole::cases(), 'value'),
        );
    }
}
