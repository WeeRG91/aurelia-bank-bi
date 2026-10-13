<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardKpiComparisonProjector;
use App\Analytics\Dashboards\DashboardKpiValue;
use Tests\TestCase;

final class DashboardKpiComparisonProjectorTest extends TestCase
{
    public function test_comparisons_are_projected_with_safe_percentages(): void
    {
        $results = app(DashboardKpiComparisonProjector::class)
            ->project(
                current: [
                    new DashboardKpiValue(
                        context: ['currency' => 'EUR'],
                        value: '125.00',
                    ),
                    new DashboardKpiValue(
                        context: ['currency' => 'GBP'],
                        value: '50.00',
                    ),
                    new DashboardKpiValue(
                        context: ['currency' => 'CHF'],
                        value: null,
                    ),
                    new DashboardKpiValue(
                        context: ['currency' => 'USD'],
                        value: '80.00',
                    ),
                ],
                previous: [
                    new DashboardKpiValue(
                        context: ['currency' => 'EUR'],
                        value: '100.00',
                    ),
                    new DashboardKpiValue(
                        context: ['currency' => 'GBP'],
                        value: '0.00',
                    ),
                    new DashboardKpiValue(
                        context: ['currency' => 'CHF'],
                        value: '25.00',
                    ),
                ],
            );

        $this->assertSame(
            [
                [
                    'context' => ['currency' => 'EUR'],
                    'current' => '125.00',
                    'previous' => '100.00',
                    'state' => 'comparable',
                    'percentage_change' => '25.00',
                ],
                [
                    'context' => ['currency' => 'GBP'],
                    'current' => '50.00',
                    'previous' => '0.00',
                    'state' => 'previous_zero',
                    'percentage_change' => null,
                ],
                [
                    'context' => ['currency' => 'CHF'],
                    'current' => null,
                    'previous' => '25.00',
                    'state' => 'missing_current',
                    'percentage_change' => null,
                ],
                [
                    'context' => ['currency' => 'USD'],
                    'current' => '80.00',
                    'previous' => null,
                    'state' => 'missing_previous',
                    'percentage_change' => null,
                ],
            ],
            array_map(
                static fn ($result): array => $result->toArray(),
                $results,
            ),
        );
    }
}
