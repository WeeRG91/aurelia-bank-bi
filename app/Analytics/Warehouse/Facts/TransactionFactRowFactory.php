<?php

namespace App\Analytics\Warehouse\Facts;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class TransactionFactRowFactory
{
    /**
     * @param  array{
     *     source_transaction_id: int,
     *     account_key: int,
     *     branch_key: int,
     *     booking_date_key: int|null,
     *     value_date_key: int|null,
     *     transaction_reference: string,
     *     transaction_type: string,
     *     category: string,
     *     amount: string,
     *     transaction_currency: string,
     *     direction: string,
     *     transaction_status: string,
     *     source_updated_at: string|null
     * }  $source
     * @return array{
     *     source_transaction_id: int,
     *     account_key: int,
     *     branch_key: int,
     *     booking_date_key: int|null,
     *     value_date_key: int|null,
     *     transaction_reference: string,
     *     transaction_type: string,
     *     category: string,
     *     currency: string,
     *     direction: string,
     *     transaction_status: string,
     *     absolute_amount: string,
     *     signed_amount: string,
     *     source_updated_at: string|null,
     *     loaded_at: string
     * }
     */
    public function create(
        array $source,
        DateTimeImmutable $loadedAt,
    ): array {
        $signedAmount = match ($source['direction']) {
            'incoming' => $source['amount'],
            'outgoing' => "-{$source['amount']}",
            default => throw new InvalidArgumentException(
                'Transaction direction must be incoming or outgoing.',
            ),
        };

        return [
            'source_transaction_id' => $source['source_transaction_id'],
            'account_key' => $source['account_key'],
            'branch_key' => $source['branch_key'],
            'booking_date_key' => $source['booking_date_key'],
            'value_date_key' => $source['value_date_key'],
            'transaction_reference' => $source['transaction_reference'],
            'transaction_type' => $source['transaction_type'],
            'category' => $source['category'],
            'currency' => $source['transaction_currency'],
            'direction' => $source['direction'],
            'transaction_status' => $source['transaction_status'],
            'absolute_amount' => $source['amount'],
            'signed_amount' => $signedAmount,
            'source_updated_at' => $source['source_updated_at'],
            'loaded_at' => $loadedAt
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d H:i:sP'),
        ];
    }
}
