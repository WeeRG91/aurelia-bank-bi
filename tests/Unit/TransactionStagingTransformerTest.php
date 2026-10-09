<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Etl\TransactionStagingTransformer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class TransactionStagingTransformerTest extends TestCase
{
    public function test_operational_transaction_is_transformed_for_staging(): void
    {
        $source = (object) [
            'source_transaction_id' => '501',
            'source_account_id' => '101',
            'source_branch_id' => '10',
            'transaction_reference' => 'TXN-ABCDEFGHIJKLMNOPQRST',
            'transaction_type' => 'transfer',
            'category' => 'internal_transfer',
            'amount' => '12345678901234567.89',
            'transaction_currency' => 'EUR',
            'direction' => 'outgoing',
            'booked_at' => '2026-10-08 12:30:00+00',
            'value_date' => '2026-10-08',
            'transaction_status' => 'booked',
            'account_number' => 'AUR-LU-000000000101',
            'account_type' => 'current',
            'account_currency' => 'EUR',
            'account_opened_at' => '2025-01-15',
            'account_closed_at' => null,
            'account_status' => 'active',
            'branch_code' => 'LU-LUX-001',
            'branch_name' => 'Luxembourg Central',
            'branch_country_code' => 'LU',
            'branch_city' => 'Luxembourg',
            'source_updated_at' => '2026-10-08 12:31:00',
        ];

        $result = (new TransactionStagingTransformer)->transform(
            $source,
            new DateTimeImmutable(
                '2026-10-08T18:45:00+00:00',
            ),
        );

        $this->assertSame(501, $result['source_transaction_id']);
        $this->assertSame(101, $result['source_account_id']);
        $this->assertSame(10, $result['source_branch_id']);

        $this->assertSame(
            '12345678901234567.89',
            $result['amount'],
        );
        $this->assertIsString($result['amount']);

        $this->assertSame(
            'TXN-ABCDEFGHIJKLMNOPQRST',
            $result['transaction_reference'],
        );
        $this->assertNull($result['account_closed_at']);
        $this->assertSame(
            '2026-10-08 18:45:00+00:00',
            $result['loaded_at'],
        );

        $this->assertSame([
            'source_transaction_id',
            'source_account_id',
            'source_branch_id',
            'transaction_reference',
            'transaction_type',
            'category',
            'amount',
            'transaction_currency',
            'direction',
            'booked_at',
            'value_date',
            'transaction_status',
            'account_number',
            'account_type',
            'account_currency',
            'account_opened_at',
            'account_closed_at',
            'account_status',
            'branch_code',
            'branch_name',
            'branch_country_code',
            'branch_city',
            'source_updated_at',
            'loaded_at',
        ], array_keys($result));
    }

    public function test_nullable_operational_values_remain_null(): void
    {
        $source = (object) [
            'source_transaction_id' => 501,
            'source_account_id' => 101,
            'source_branch_id' => 10,
            'transaction_reference' => 'TXN-ABCDEFGHIJKLMNOPQRST',
            'transaction_type' => 'transfer',
            'category' => 'internal_transfer',
            'amount' => '100.00',
            'transaction_currency' => 'EUR',
            'direction' => 'outgoing',
            'booked_at' => null,
            'value_date' => null,
            'transaction_status' => 'pending',
            'account_number' => 'AUR-LU-000000000101',
            'account_type' => 'current',
            'account_currency' => 'EUR',
            'account_opened_at' => '2025-01-15',
            'account_closed_at' => null,
            'account_status' => 'active',
            'branch_code' => 'LU-LUX-001',
            'branch_name' => 'Luxembourg Central',
            'branch_country_code' => 'LU',
            'branch_city' => 'Luxembourg',
            'source_updated_at' => null,
        ];

        $result = (new TransactionStagingTransformer)->transform(
            $source,
            new DateTimeImmutable(
                '2026-10-08T18:45:00+00:00',
            ),
        );

        $this->assertNull($result['booked_at']);
        $this->assertNull($result['value_date']);
        $this->assertNull($result['account_closed_at']);
        $this->assertNull($result['source_updated_at']);
    }
}
