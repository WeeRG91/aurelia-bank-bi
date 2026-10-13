<?php

namespace App\Analytics\Dashboards;

use LogicException;

final class DashboardKpiPercentageCalculator
{
    public function calculate(
        DashboardKpiComparisonValue $comparison,
    ): ?string {
        if (
            $comparison->state
            !== DashboardKpiComparisonState::COMPARABLE
        ) {
            return null;
        }

        $current = $comparison->current;
        $previous = $comparison->previous;

        if (
            $current === null
            || $previous === null
            || ! is_numeric($current)
            || ! is_numeric($previous)
        ) {
            throw new LogicException(
                'Comparable KPI values must be numeric.',
            );
        }

        if (bccomp((string) $previous, '0', 10) === 0) {
            throw new LogicException(
                'A zero previous KPI value cannot produce a percentage change.',
            );
        }

        $difference = bcsub(
            (string) $current,
            (string) $previous,
            10,
        );

        $absolutePrevious = $this->absolute(
            (string) $previous,
        );

        $percentage = bcdiv(
            bcmul($difference, '100', 10),
            $absolutePrevious,
            6,
        );

        return bcadd(
            $percentage,
            str_starts_with($percentage, '-')
                ? '-0.005'
                : '0.005',
            2,
        );
    }

    private function absolute(string $value): string
    {
        return str_starts_with($value, '-')
            ? substr($value, 1)
            : $value;
    }
}
