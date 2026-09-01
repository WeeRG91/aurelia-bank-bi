<?php

namespace App\Analytics\Time;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\FieldDataType;
use App\Analytics\Filters\FilterCondition;
use App\Analytics\Filters\FilterOperator;
use App\Analytics\Filters\FilterValidator;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

final readonly class RelativeDateFilterFactory
{
    public function __construct(
        private DatasetRegistry $registry,
        private FilterValidator $validator,
        private RelativeDateRangeResolver $rangeResolver,
    ) {}

    /**
     * @return list<FilterCondition>
     */
    public function create(
        DatasetKey|string $datasetIdentifier,
        string $dimensionKey,
        RelativeDatePreset $preset,
        DateTimeImmutable $now,
        DateTimeZone $reportingTimeZone,
    ): array {
        $dataset = $this->registry->get($datasetIdentifier);
        $dimension = $dataset->dimension($dimensionKey);

        if (
            ! in_array(
                $dimension->dataType,
                [
                    FieldDataType::DATE,
                    FieldDataType::DATETIME,
                ],
                true,
            )
        ) {
            throw new InvalidArgumentException(
                "Relative date filter requires a date or datetime dimension; [{$dimensionKey}] is not temporal.",
            );
        }

        $range = $this->rangeResolver->resolve($preset, $now, $reportingTimeZone);

        if ($dimension->dataType === FieldDataType::DATE) {
            if ($range->startDate === $range->endDate) {
                return [
                    $this->validator->validate(
                        $dataset->key,
                        $dimensionKey,
                        FilterOperator::EQUALS,
                        $range->startDate,
                    ),
                ];
            }

            return [
                $this->validator->validate(
                    $dataset->key,
                    $dimensionKey,
                    FilterOperator::BETWEEN,
                    [
                        $range->startDate,
                        $range->endDate,
                    ],
                ),
            ];
        }

        $start = CarbonImmutable::parse(
            $range->startDate,
            $reportingTimeZone,
        )
            ->startOfDay()
            ->utc()
            ->format(DateTimeInterface::ATOM);

        $exclusiveEnd = CarbonImmutable::parse(
            $range->endDate,
            $reportingTimeZone,
        )
            ->addDay()
            ->startOfDay()
            ->utc()
            ->format(DateTimeInterface::ATOM);

        return [
            $this->validator->validate(
                $dataset->key,
                $dimensionKey,
                FilterOperator::ON_OR_AFTER,
                $start,
            ),
            $this->validator->validate(
                $dataset->key,
                $dimensionKey,
                FilterOperator::BEFORE,
                $exclusiveEnd,
            ),
        ];
    }
}
