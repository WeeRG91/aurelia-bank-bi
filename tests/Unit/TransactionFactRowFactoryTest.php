<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Facts\TransactionFactRowFactory;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TransactionFactRowFactoryTest extends TestCase
{
    public function test_incoming_transaction_is_mapped_to_fact_row(): void
    {
        $row = (new TransactionFactRowFactory)->create(
            [
                'source_transaction_id' => 101,
                'account_key' => 11,
                'branch_key' => 2,
                'booking_date_key' => 20261012,
                'value_date_key' => 20261011,
                'booked_at' => '2026-10-12T15:45:00+02:00',
                'transaction_reference' => 'TXN-D0000000000000000101',
                'transaction_type' => 'transfer',
                'category' => 'salary',
                'amount' => '1250.50',
                'transaction_currency' => 'EUR',
                'direction' => 'incoming',
                'transaction_status' => 'booked',
                'source_updated_at' => '2026-10-12 08:00:00',
            ],
            new DateTimeImmutable(
                '2026-10-12T15:30:00+02:00',
            ),
        );

        $this->assertSame(
            [
                'source_transaction_id' => 101,
                'account_key' => 11,
                'branch_key' => 2,
                'booking_date_key' => 20261012,
                'value_date_key' => 20261011,
                'booked_at' => '2026-10-12 13:45:00+00:00',
                'transaction_reference' => 'TXN-D0000000000000000101',
                'transaction_type' => 'transfer',
                'category' => 'salary',
                'currency' => 'EUR',
                'direction' => 'incoming',
                'transaction_status' => 'booked',
                'absolute_amount' => '1250.50',
                'signed_amount' => '1250.50',
                'source_updated_at' => '2026-10-12 08:00:00',
                'loaded_at' => '2026-10-12 13:30:00+00:00',
            ],
            $row,
        );
    }

    public function test_outgoing_pending_transaction_has_negative_amount_and_null_dates(): void
    {
        $row = (new TransactionFactRowFactory)->create(
            [
                'source_transaction_id' => 102,
                'account_key' => 12,
                'branch_key' => 3,
                'booking_date_key' => null,
                'value_date_key' => null,
                'booked_at' => null,
                'transaction_reference' => 'TXN-D0000000000000000102',
                'transaction_type' => 'fee',
                'category' => 'bank_fee',
                'amount' => '12.75',
                'transaction_currency' => 'EUR',
                'direction' => 'outgoing',
                'transaction_status' => 'pending',
                'source_updated_at' => null,
            ],
            new DateTimeImmutable(
                '2026-10-12T13:30:00Z',
            ),
        );

        $this->assertSame(
            '12.75',
            $row['absolute_amount'],
        );

        $this->assertSame(
            '-12.75',
            $row['signed_amount'],
        );

        $this->assertNull($row['booking_date_key']);
        $this->assertNull($row['value_date_key']);
        $this->assertNull($row['booked_at']);
    }

    public function test_unknown_direction_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        (new TransactionFactRowFactory)->create(
            [
                'source_transaction_id' => 103,
                'account_key' => 12,
                'branch_key' => 3,
                'booking_date_key' => 20261012,
                'value_date_key' => 20261012,
                'booked_at' => '2026-10-12T13:30:00Z',
                'transaction_reference' => 'TXN-D0000000000000000103',
                'transaction_type' => 'transfer',
                'category' => 'internal_transfer',
                'amount' => '100.00',
                'transaction_currency' => 'EUR',
                'direction' => 'sideways',
                'transaction_status' => 'booked',
                'source_updated_at' => null,
            ],
            new DateTimeImmutable(
                '2026-10-12T13:30:00Z',
            ),
        );
    }
}
