<?php

namespace App\Analytics\Queries;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Filters\FilterCondition;
use InvalidArgumentException;

final readonly class DatasetQuery
{
    private const int MAX_DIMENSIONS = 20;

    private const int MAX_FILTERS = 20;

    private const int MAX_LIMIT = 500;

    /**
     * @param  list<string>  $dimensions
     * @param  list<FilterCondition>  $filters
     */
    public function __construct(
        public DatasetKey $dataset,
        public array $dimensions,
        public array $filters = [],
        public int $limit = 100,
    ) {
        if (
            $this->dimensions === []
            || ! array_is_list($this->dimensions)
            || count($this->dimensions) > self::MAX_DIMENSIONS
        ) {
            throw new InvalidArgumentException(
                'A dataset query requires between 1 and 20 dimensions.',
            );
        }

        if (count(array_unique($this->dimensions)) !== count($this->dimensions)) {
            throw new InvalidArgumentException(
                'Dataset query dimensions must be unique.',
            );
        }

        foreach ($this->dimensions as $dimension) {
            if (
                ! is_string($dimension)
                || preg_match('/^[a-z][a-z0-9_]*$/', $dimension) !== 1
            ) {
                throw new InvalidArgumentException(
                    'Dataset query dimensions must use safe semantic keys.',
                );
            }
        }

        if (
            ! array_is_list($this->filters)
            || count($this->filters) > self::MAX_FILTERS
        ) {
            throw new InvalidArgumentException(
                'A dataset query cannot contain more than 20 filters.',
            );
        }

        foreach ($this->filters as $filter) {
            if (! $filter instanceof FilterCondition) {
                throw new InvalidArgumentException(
                    'Dataset query filters must be validated filter conditions.',
                );
            }

            if ($filter->dataset !== $this->dataset) {
                throw new InvalidArgumentException(
                    "Filter dataset [{$filter->dataset->value}] does not match query dataset [{$this->dataset->value}].",
                );
            }
        }

        if ($this->limit < 1 || $this->limit > self::MAX_LIMIT) {
            throw new InvalidArgumentException(
                'Dataset query limit must be between 1 and 500.',
            );
        }
    }
}
