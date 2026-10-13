<?php

namespace App\Analytics\Dashboards;

use JsonException;

final readonly class DashboardKpiComparisonProjector
{
    public function __construct(
        private DashboardKpiComparisonMatcher $matcher,
        private DashboardKpiPercentageCalculator $percentageCalculator,
    ) {}

    /**
     * @param  list<DashboardKpiValue>  $current
     * @param  list<DashboardKpiValue>  $previous
     * @return list<DashboardKpiComparisonResult>
     *
     * @throws JsonException
     */
    public function project(
        array $current,
        array $previous,
    ): array {
        return array_map(
            fn (
                DashboardKpiComparisonValue $comparison,
            ): DashboardKpiComparisonResult => new DashboardKpiComparisonResult(
                context: $comparison->context,
                current: $comparison->current,
                previous: $comparison->previous,
                state: $comparison->state,
                percentageChange: $this->percentageCalculator
                    ->calculate($comparison),
            ),
            $this->matcher->match(
                current: $current,
                previous: $previous,
            ),
        );
    }
}
