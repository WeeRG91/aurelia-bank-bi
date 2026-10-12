<?php

namespace App\Analytics\Warehouse\Dimensions;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;

final readonly class AccountDimensionRowFactory
{
    public function __construct(
        private DimensionAttributeHasher $hasher,
    ) {}

    /**
     * @param array{
     *     source_account_id: int,
     *     account_number: string,
     *     account_type: string,
     *     account_currency: string,
     *     account_opened_at: string,
     *     account_closed_at: string|null,
     *     account_status: string
     * } $source
     * @return array{
     *     source_account_id: int,
     *     account_number: string,
     *     account_type: string,
     *     currency: string,
     *     opened_at: string,
     *     closed_at: string|null,
     *     status: string,
     *     source_updated_at: null,
     *     loaded_at: string,
     *     attribute_hash: string,
     * }
     *
     * @throws JsonException
     */
    public function create(
        array $source,
        DateTimeImmutable $loadedAt,
    ): array {
        $attributes = [
            'account_number' => $source['account_number'],
            'account_type' => $source['account_type'],
            'currency' => $source['account_currency'],
            'opened_at' => $source['account_opened_at'],
            'closed_at' => $source['account_closed_at'],
            'status' => $source['account_status'],
        ];

        return [
            'source_account_id' => $source['source_account_id'],
            ...$attributes,
            'source_updated_at' => null,
            'loaded_at' => $loadedAt
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d H:i:sP'),
            'attribute_hash' => $this->hasher->hash($attributes),
        ];
    }
}
