<?php

namespace App\Analytics\Queries;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Filters\FilterCondition;
use App\Analytics\Filters\FilterValidator;
use App\Analytics\Time\ComparisonDatasetQueryFactory;
use App\Analytics\Time\PeriodComparison;
use App\Analytics\Time\RelativeDatePreset;
use App\Analytics\Time\ReportingTimezone;
use DateInvalidTimeZoneException;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class ReportDefinitionComparisonQueryFactory
{
    public function __construct(
        private FilterValidator $filterValidator,
        private ComparisonDatasetQueryFactory $comparisonFactory,
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
        PeriodComparison $comparison = PeriodComparison::PREVIOUS_PERIOD,
    ): DatasetQueryComparison {
        $relativeDate = $definition['relative_date']
            ?? null;

        if (
            ! is_array($relativeDate)
            || ! is_string(
                $relativeDate['dimension'] ?? null,
            )
            || ! is_string(
                $relativeDate['preset'] ?? null,
            )
        ) {
            throw new InvalidArgumentException(
                'A comparison query requires a valid relative date definition.',
            );
        }

        $filters = array_map(
            fn (array $filter): FilterCondition => $this
                ->filterValidator
                ->validate(
                    $dataset,
                    $filter['dimension'],
                    $filter['operator'],
                    $filter['value'],
                ),
            $definition['filters'] ?? [],
        );

        return $this->comparisonFactory->create(
            dataset: $dataset,
            dimensions: $definition['dimensions'],
            measures: $definition['measures'],
            filters: $filters,
            relativeDateDimension: $relativeDate[
            'dimension'
            ],
            preset: RelativeDatePreset::from(
                $relativeDate['preset'],
            ),
            comparisonPeriod: $comparison,
            now: $now,
            reportingTimezone: $reportingTimezone,
            limit: $definition['limit'] ?? 100,
        );
    }
}
