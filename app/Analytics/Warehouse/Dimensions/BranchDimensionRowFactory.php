<?php

namespace App\Analytics\Warehouse\Dimensions;

use DateTimeImmutable;
use DateTimeZone;

final readonly class BranchDimensionRowFactory
{
    /**
     * @param  array{
     *     source_branch_id: int,
     *     branch_code: string,
     *     branch_name: string,
     *     branch_country_code: string,
     *     branch_city: string
     * }  $source
     * @return array{
     *     source_branch_id: int,
     *     branch_code: string,
     *     branch_name: string,
     *     country_code: string,
     *     city: string,
     *     source_updated_at: null,
     *     loaded_at: string
     * }
     */
    public function create(
        array $source,
        DateTimeImmutable $loadedAt,
    ): array {
        return [
            'source_branch_id' => $source['source_branch_id'],
            'branch_code' => $source['branch_code'],
            'branch_name' => $source['branch_name'],
            'country_code' => $source['branch_country_code'],
            'city' => $source['branch_city'],
            'source_updated_at' => null,
            'loaded_at' => $loadedAt
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d H:i:sP'),
        ];
    }
}
