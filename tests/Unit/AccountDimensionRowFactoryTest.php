<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Dimensions\AccountDimensionRowFactory;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AccountDimensionRowFactoryTest extends TestCase
{
    public function test_staged_account_is_mapped_to_dimension_row(): void
    {
        $row = (new AccountDimensionRowFactory)->create(
            [
                'source_account_id' => 84,
                'account_number' => 'AUR-LU-000000000084',
                'account_type' => 'current',
                'account_currency' => 'EUR',
                'account_opened_at' => '2021-03-15',
                'account_closed_at' => null,
                'account_status' => 'active',
            ],
            new DateTimeImmutable(
                '2026-10-12T14:30:00+02:00',
            ),
        );

        $this->assertSame(
            [
                'source_account_id' => 84,
                'account_number' => 'AUR-LU-000000000084',
                'account_type' => 'current',
                'currency' => 'EUR',
                'opened_at' => '2021-03-15',
                'closed_at' => null,
                'status' => 'active',
                'source_updated_at' => null,
                'loaded_at' => '2026-10-12 12:30:00+00:00',
            ],
            $row,
        );
    }

    public function test_closed_account_preserves_its_closing_date(): void
    {
        $row = (new AccountDimensionRowFactory)->create(
            [
                'source_account_id' => 85,
                'account_number' => 'AUR-LU-000000000085',
                'account_type' => 'savings',
                'account_currency' => 'EUR',
                'account_opened_at' => '2020-01-10',
                'account_closed_at' => '2026-06-30',
                'account_status' => 'closed',
            ],
            new DateTimeImmutable(
                '2026-10-12T12:30:00+00:00',
            ),
        );

        $this->assertSame(
            '2026-06-30',
            $row['closed_at'],
        );

        $this->assertSame(
            'closed',
            $row['status'],
        );
    }
}
