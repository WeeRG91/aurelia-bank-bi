<?php

namespace App\Analytics\Filters;

use App\Analytics\Datasets\DatasetKey;

final readonly class FilterCondition
{
    /**
     * @param  string|int|bool|list<string|int|bool>|null  $value
     */
    public function __construct(
        public DatasetKey $dataset,
        public string $dimension,
        public FilterOperator $operator,
        public string|int|bool|array|null $value,
    ) {}
}
