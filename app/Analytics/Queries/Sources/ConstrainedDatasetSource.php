<?php

namespace App\Analytics\Queries\Sources;

use App\Analytics\Queries\Compilation\CompiledFilter;
use App\Analytics\Queries\DatasetQuery;

interface ConstrainedDatasetSource extends DatasetSource
{
    /**
     * @return list<CompiledFilter>
     */
    public function constraints(
        DatasetQuery $query,
    ): array;
}
