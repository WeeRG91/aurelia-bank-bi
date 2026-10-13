<?php

namespace App\Analytics\Dashboards;

use JsonException;
use LogicException;

final class DashboardKpiComparisonMatcher
{
    /**
     * @param  list<DashboardKpiValue>  $current
     * @param  list<DashboardKpiValue>  $previous
     * @return list<DashboardKpiComparisonValue>
     *
     * @throws JsonException
     */
    public function match(
        array $current,
        array $previous,
    ): array {
        $previousByContext = $this->indexByContext(
            $previous,
        );

        return array_map(
            function (
                DashboardKpiValue $currentValue,
            ) use (
                $previousByContext,
            ): DashboardKpiComparisonValue {
                $key = $this->contextKey(
                    $currentValue->context,
                );

                $previousValue = $previousByContext[$key]
                    ?? null;

                if ($currentValue->value === null) {
                    return new DashboardKpiComparisonValue(
                        context: $currentValue->context,
                        current: null,
                        previous: $previousValue?->value,
                        state: DashboardKpiComparisonState::MISSING_CURRENT,
                    );
                }

                if (
                    ! $previousValue instanceof DashboardKpiValue
                    || $previousValue->value === null
                ) {
                    return new DashboardKpiComparisonValue(
                        context: $currentValue->context,
                        current: $currentValue->value,
                        previous: null,
                        state: DashboardKpiComparisonState::MISSING_PREVIOUS,
                    );
                }

                return new DashboardKpiComparisonValue(
                    context: $currentValue->context,
                    current: $currentValue->value,
                    previous: $previousValue->value,
                    state: $this->isZero(
                        $previousValue->value,
                    )
                        ? DashboardKpiComparisonState::PREVIOUS_ZERO
                        : DashboardKpiComparisonState::COMPARABLE,
                );
            },
            $current,
        );
    }

    /**
     * @param  list<DashboardKpiValue>  $values
     * @return array<string, DashboardKpiValue>
     *
     * @throws JsonException
     */
    private function indexByContext(
        array $values,
    ): array {
        $indexed = [];

        foreach ($values as $value) {
            $key = $this->contextKey($value->context);

            if (isset($indexed[$key])) {
                throw new LogicException(
                    'KPI comparison contains duplicate contexts.',
                );
            }

            $indexed[$key] = $value;
        }

        return $indexed;
    }

    /**
     * @param  array<string, bool|int|string|null>  $context
     *
     * @throws JsonException
     */
    private function contextKey(array $context): string
    {
        ksort($context);

        return json_encode(
            $context,
            JSON_THROW_ON_ERROR,
        );
    }

    private function isZero(
        int|string|null $value,
    ): bool {
        if ($value === null) {
            return false;
        }

        if (is_int($value)) {
            return $value === 0;
        }

        if (! is_numeric($value)) {
            throw new LogicException(
                'KPI comparison values must be numeric.',
            );
        }

        return bccomp($value, '0', 10) === 0;
    }
}
