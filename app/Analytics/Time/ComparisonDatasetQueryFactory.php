<?php

namespace App\Analytics\Time;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Filters\FilterCondition;
use App\Analytics\Queries\DatasetQuery;
use App\Analytics\Queries\DatasetQueryComparison;
use DateInvalidTimeZoneException;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class ComparisonDatasetQueryFactory
{
    public function __construct(
        private RelativeDateRangeResolver $relativeRangeResolver,
        private ComparisonDateRangeResolver $comparisonRangeResolver,
        private RelativeDateFilterFactory $filterFactory,
    ) {}

    /**
     * @param  list<string>  $dimensions
     * @param  list<string>  $measures
     * @param  list<FilterCondition>  $filters
     *
     * @throws DateInvalidTimeZoneException
     */
    public function create(
        DatasetKey $dataset,
        array $dimensions,
        array $measures,
        array $filters,
        string $relativeDateDimension,
        RelativeDatePreset $preset,
        PeriodComparison $comparisonPeriod,
        DateTimeImmutable $now,
        ReportingTimezone $reportingTimezone,
        int $limit = 100
    ): DatasetQueryComparison {
        foreach ($filters as $filter) {
            if ($filter->dimension === $relativeDateDimension) {
                throw new InvalidArgumentException(
                    "Explicit filter for [{$relativeDateDimension}] cannot be combined with a period comparison on the same dimension.",
                );
            }
        }

        $nativeTimezone = $reportingTimezone->toDateTimeZone();

        $currentRange = $this->relativeRangeResolver->resolve(
            $preset,
            $now,
            $nativeTimezone,
        );

        $comparisonRange = $this->comparisonRangeResolver->resolve(
            $currentRange,
            $comparisonPeriod,
        );

        $current = $this->queryForRange(
            $dataset,
            $dimensions,
            $measures,
            $filters,
            $relativeDateDimension,
            $currentRange,
            $reportingTimezone,
            $limit,
        );

        $comparison = $this->queryForRange(
            $dataset,
            $dimensions,
            $measures,
            $filters,
            $relativeDateDimension,
            $comparisonRange,
            $reportingTimezone,
            $limit,
        );

        return new DatasetQueryComparison(
            current: $current,
            comparison: $comparison,
            currentRange: $currentRange,
            comparisonRange: $comparisonRange,
            comparisonPeriod: $comparisonPeriod,
        );
    }

    /**
     * @param  list<string>  $dimensions
     * @param  list<string>  $measures
     * @param  list<FilterCondition>  $filters
     *
     * @throws DateInvalidTimeZoneException
     */
    private function queryForRange(
        DatasetKey $dataset,
        array $dimensions,
        array $measures,
        array $filters,
        string $dateDimension,
        DateRange $range,
        ReportingTimezone $reportingTimezone,
        int $limit,
    ): DatasetQuery {
        $dateFilters = $this->filterFactory->createForRange(
            $dataset,
            $dateDimension,
            $range,
            $reportingTimezone->toDateTimeZone(),
        );

        return new DatasetQuery(
            dataset: $dataset,
            dimensions: $dimensions,
            measures: $measures,
            filters: [
                ...$filters,
                ...$dateFilters,
            ],
            limit: $limit,
            reportingTimezone: $reportingTimezone,
        );
    }
}
