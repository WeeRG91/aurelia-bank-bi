<?php

namespace App\Analytics\Queries;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Filters\FilterCondition;
use App\Analytics\Filters\FilterValidator;
use App\Analytics\Time\RelativeDateDatasetQueryFactory;
use App\Analytics\Time\RelativeDatePreset;
use App\Analytics\Time\ReportingTimezone;
use DateInvalidTimeZoneException;
use DateTimeImmutable;

final readonly class ReportDefinitionQueryFactory
{
    public function __construct(
        private FilterValidator $filterValidator,
        private RelativeDateDatasetQueryFactory $relativeDateFactory,
    ) {}

    /**
     * @param array{
     *     dimensions: list<string>,
     *     measures: list<string>,
     *     filters?: list<array{
     *         dimension: string,
     *         operator: string,
     *         value: mixed
     *     }>,
     *     relative_date?: array{
     *         dimension: string,
     *         preset: string
     *     }|null,
     *     limit?: int
     * } $definition
     *
     * @throws DateInvalidTimeZoneException
     */
    public function create(
        DatasetKey $dataset,
        array $definition,
        DateTimeImmutable $now,
        ReportingTimezone $reportingTimezone,
    ): DatasetQuery {
        $filters = array_map(
            fn (array $filter): FilterCondition => $this
                ->filterValidator
                ->validate(
                    $dataset,
                    $filter['dimension'],
                    $filter['operator'],
                    $filter['value']
                ),
            $definition['filters'] ?? [],
        );

        $relativeDate = $definition['relative_date'] ?? null;

        if ($relativeDate !== null) {
            return $this->relativeDateFactory->create(
                dataset: $dataset,
                dimensions: $definition['dimensions'],
                measures: $definition['measures'],
                filters: $filters,
                relativeDateDimension: $relativeDate['dimension'],
                preset: RelativeDatePreset::from(
                    $relativeDate['preset'],
                ),
                now: $now,
                reportingTimezone: $reportingTimezone,
                limit: $definition['limit'] ?? 100,
            );
        }

        return new DatasetQuery(
            dataset: $dataset,
            dimensions: $definition['dimensions'],
            measures: $definition['measures'],
            filters: $filters,
            limit: $definition['limit'] ?? 100,
            reportingTimezone: $reportingTimezone,
        );
    }
}
