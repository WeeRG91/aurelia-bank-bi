<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardKpiComparisonMatcher;
use App\Analytics\Dashboards\DashboardKpiValue;
use Tests\TestCase;

final class DashboardKpiComparisonMatcherTest extends TestCase
{
    public function test_values_are_matched_by_context(): void
    {
        $comparisons = (new DashboardKpiComparisonMatcher)
            ->match(
                current: [
                    new DashboardKpiValue(
                        context: ['currency' => 'EUR'],
                        value: '1500.00',
                    ),
                    new DashboardKpiValue(
                        context: ['currency' => 'CHF'],
                        value: '900.00',
                    ),
                ],
                previous: [
                    new DashboardKpiValue(
                        context: ['currency' => 'CHF'],
                        value: '800.00',
                    ),
                    new DashboardKpiValue(
                        context: ['currency' => 'EUR'],
                        value: '1200.00',
                    ),
                ],
            );

        $this->assertSame(
            [
                [
                    'context' => ['currency' => 'EUR'],
                    'current' => '1500.00',
                    'previous' => '1200.00',
                    'state' => 'comparable',
                ],
                [
                    'context' => ['currency' => 'CHF'],
                    'current' => '900.00',
                    'previous' => '800.00',
                    'state' => 'comparable',
                ],
            ],
            array_map(
                static fn ($comparison): array => $comparison
                    ->toArray(),
                $comparisons,
            ),
        );
    }

    public function test_missing_previous_context_is_explicit(): void
    {
        $comparisons = (new DashboardKpiComparisonMatcher)
            ->match(
                current: [
                    new DashboardKpiValue(
                        context: ['currency' => 'EUR'],
                        value: '1500.00',
                    ),
                ],
                previous: [],
            );

        $this->assertSame(
            'missing_previous',
            $comparisons[0]->state->value,
        );

        $this->assertNull(
            $comparisons[0]->previous,
        );
    }

    public function test_zero_previous_value_is_not_comparable(): void
    {
        $comparisons = (new DashboardKpiComparisonMatcher)
            ->match(
                current: [
                    new DashboardKpiValue(
                        context: [],
                        value: 15,
                    ),
                ],
                previous: [
                    new DashboardKpiValue(
                        context: [],
                        value: 0,
                    ),
                ],
            );

        $this->assertSame(
            'previous_zero',
            $comparisons[0]->state->value,
        );

        $this->assertSame(
            0,
            $comparisons[0]->previous,
        );
    }

    public function test_missing_current_value_is_explicit(): void
    {
        $comparisons = (new DashboardKpiComparisonMatcher)
            ->match(
                current: [
                    new DashboardKpiValue(
                        context: [],
                        value: null,
                    ),
                ],
                previous: [
                    new DashboardKpiValue(
                        context: [],
                        value: 10,
                    ),
                ],
            );

        $this->assertSame(
            'missing_current',
            $comparisons[0]->state->value,
        );

        $this->assertNull(
            $comparisons[0]->current,
        );

        $this->assertSame(
            10,
            $comparisons[0]->previous,
        );
    }
}
