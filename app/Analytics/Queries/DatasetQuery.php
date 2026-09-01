<?php

namespace App\Analytics\Queries;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Filters\FilterCondition;
use InvalidArgumentException;

final readonly class DatasetQuery
{
    private const int MAX_DIMENSIONS = 20;

    private const int MAX_MEASURES = 10;

    private const int MAX_FILTERS = 20;

    private const int MAX_LIMIT = 500;

    /**
     * @param  list<string>  $dimensions
     * @param  list<string>  $measures
     * @param  list<FilterCondition>  $filters
     */
    public function __construct(
        public DatasetKey $dataset,
        public array $dimensions = [],
        public array $measures = [],
        public array $filters = [],
        public int $limit = 100,
    ) {
        if (
            ! array_is_list($this->dimensions)
            || count($this->dimensions) > self::MAX_DIMENSIONS
        ) {
            throw new InvalidArgumentException(
                'A dataset query cannot contain more than 20 dimensions.',
            );
        }

        if (
            ! array_is_list($this->measures)
            || count($this->measures) > self::MAX_MEASURES
        ) {
            throw new InvalidArgumentException(
                'A dataset query cannot contain more than 10 measures.',
            );
        }

        if ($this->dimensions === [] && $this->measures === []) {
            throw new InvalidArgumentException(
                'A dataset query requires at least one dimension or measure.',
            );
        }

        $this->validateSemanticKeys(
            $this->dimensions,
            'dimensions',
        );

        $this->validateSemanticKeys(
            $this->measures,
            'measures',
        );

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

    private function validateSemanticKeys(
        array $keys,
        string $kind,
    ): void {
        foreach ($keys as $key) {
            if (
                ! is_string($key)
                || preg_match('/^[a-z][a-z0-9_]*$/', $key) !== 1
            ) {
                throw new InvalidArgumentException(
                    "Dataset query {$kind} must use safe semantic keys.",
                );
            }
        }

        if (count(array_unique($keys)) !== count($keys)) {
            throw new InvalidArgumentException(
                "Dataset query {$kind} must be unique.",
            );
        }
    }
}
