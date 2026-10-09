<?php

namespace App\Analytics\Warehouse\Etl;

use DateTimeInterface;

final class TransactionStagingTransformer
{
    /**
     * @return array<string, int|string|null>
     */
    public function transform(
        object $source,
        DateTimeInterface $loadedAt,
    ): array {
        return [
            'source_transaction_id' => (int) $source->source_transaction_id,
            'source_account_id' => (int) $source->source_account_id,
            'source_branch_id' => (int) $source->source_branch_id,

            'transaction_reference' => (string) $source->transaction_reference,
            'transaction_type' => (string) $source->transaction_type,
            'category' => (string) $source->category,

            'amount' => (string) $source->amount,

            'transaction_currency' => (string) $source->transaction_currency,
            'direction' => (string) $source->direction,
            'booked_at' => $this->nullableString($source->booked_at),
            'value_date' => $this->nullableString($source->value_date),
            'transaction_status' => (string) $source->transaction_status,

            'account_number' => (string) $source->account_number,
            'account_type' => (string) $source->account_type,
            'account_currency' => (string) $source->account_currency,
            'account_opened_at' => (string) $source->account_opened_at,
            'account_closed_at' => $this->nullableString(
                $source->account_closed_at,
            ),
            'account_status' => (string) $source->account_status,

            'branch_code' => (string) $source->branch_code,
            'branch_name' => (string) $source->branch_name,
            'branch_country_code' => (string) $source->branch_country_code,
            'branch_city' => (string) $source->branch_city,

            'source_updated_at' => $this->nullableString(
                $source->source_updated_at,
            ),
            'loaded_at' => $loadedAt->format('Y-m-d H:i:sP'),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
