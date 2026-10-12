<?php

namespace App\Analytics\Warehouse\Dimensions;

interface BranchDimensionSource
{
    /**
     * @return iterable<array{
     *     source_branch_id: int,
     *     branch_code: string,
     *     branch_name: string,
     *     branch_country_code: string,
     *     branch_city: string
     * }>
     */
    public function rows(): iterable;
}
