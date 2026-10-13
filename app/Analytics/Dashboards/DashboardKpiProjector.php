<?php

namespace App\Analytics\Dashboards;

use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Queries\DatasetQuery;
use LogicException;

final readonly class DashboardKpiProjector
{
    public function __construct(
        private DatasetRegistry $datasets,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<DashboardKpiValue>
     */
    public function project(
        DatasetQuery $query,
        array $rows,
    ): array {
        if (count($query->measures) !== 1) {
            throw new LogicException(
                'A KPI query must contain exactly one measure.',
            );
        }

        $measureKey = $query->measures[0];

        $measure = $this->datasets
            ->get($query->dataset)
            ->findMeasure($measureKey);

        if ($measure === null) {
            throw new LogicException(
                "Unknown KPI measure [{$measureKey}].",
            );
        }

        $requiredDimensions = $measure
            ->requiredContextDimensions();

        $queryDimensions = $query->dimensions;

        sort($requiredDimensions);
        sort($queryDimensions);

        if ($queryDimensions !== $requiredDimensions) {
            throw new LogicException(
                'KPI query dimensions do not match the measure context.',
            );
        }

        return array_map(
            function (array $row) use (
                $measureKey,
                $requiredDimensions,
            ): DashboardKpiValue {
                if (! array_key_exists($measureKey, $row)) {
                    throw new LogicException(
                        "KPI result is missing measure [{$measureKey}].",
                    );
                }

                $value = $row[$measureKey];

                if (
                    ! is_int($value)
                    && ! is_string($value)
                    && $value !== null
                ) {
                    throw new LogicException(
                        'KPI values must preserve integer or decimal-string precision.',
                    );
                }

                $context = [];

                foreach ($requiredDimensions as $dimension) {
                    if (! array_key_exists($dimension, $row)) {
                        throw new LogicException(
                            "KPI result is missing context dimension [{$dimension}].",
                        );
                    }

                    $contextValue = $row[$dimension];

                    if (
                        ! is_bool($contextValue)
                        && ! is_int($contextValue)
                        && ! is_string($contextValue)
                        && $contextValue !== null
                    ) {
                        throw new LogicException(
                            "KPI context dimension [{$dimension}] has an invalid value.",
                        );
                    }

                    $context[$dimension] = $contextValue;
                }

                return new DashboardKpiValue(
                    context: $context,
                    value: $value,
                );
            },
            $rows,
        );
    }
}
