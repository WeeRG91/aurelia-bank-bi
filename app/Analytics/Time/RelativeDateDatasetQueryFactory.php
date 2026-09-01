<?php

namespace App\Analytics\Time;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Queries\DatasetQuery;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class RelativeDateDatasetQueryFactory
{
    public function __construct(
        private RelativeDateFilterFactory $filterFactory,
    ) {}

    /**
     * @param  list<string>  $dimensions
     * @param  list<string>  $measures
     * @param  list<string>  $filters
     */
    public function create(
        DatasetKey $dataset,
        array $dimensions,
        array $measures,
        array $filters,
        string $relativeDateDimension,
        RelativeDatePreset $preset,
        DateTimeImmutable $now,
        DateTimeZone $reportingTimezone,
        int $limit = 100,
    ): DatasetQuery {
        foreach ($filters as $filter) {
            if ($filter->dimension === $relativeDateDimension) {
                throw new InvalidArgumentException(
                    "Explicit filter for [{$relativeDateDimension}] cannot be combined with a relative date preset on the same dimension.",
                );
            }
        }

        $relativeFilters = $this->filterFactory->create(
            $dataset,
            $relativeDateDimension,
            $preset,
            $now,
            $reportingTimezone,
        );

        return new DatasetQuery(
            dataset: $dataset,
            dimensions: $dimensions,
            measures: $measures,
            filters: [
                ...$filters,
                ...$relativeFilters,
            ],
            limit: $limit,
        );
    }
}
