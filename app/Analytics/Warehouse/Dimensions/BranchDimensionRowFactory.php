<?php

namespace App\Analytics\Warehouse\Dimensions;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;

final readonly class BranchDimensionRowFactory
{
    public function __construct(
        private DimensionAttributeHasher $hasher,
    ) {}

    /**
     * @param array{
     *     source_branch_id: int,
     *     branch_code: string,
     *     branch_name: string,
     *     branch_country_code: string,
     *     branch_city: string
     * } $source
     * @return array{
     *     source_branch_id: int,
     *     branch_code: string,
     *     branch_name: string,
     *     country_code: string,
     *     city: string,
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
            'branch_code' => $source['branch_code'],
            'branch_name' => $source['branch_name'],
            'country_code' => $source['branch_country_code'],
            'city' => $source['branch_city'],
        ];

        return [
            'source_branch_id' => $source['source_branch_id'],
            ...$attributes,
            'source_updated_at' => null,
            'loaded_at' => $loadedAt
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d H:i:sP'),
            'attribute_hash' => $this->hasher->hash($attributes),
        ];
    }
}
