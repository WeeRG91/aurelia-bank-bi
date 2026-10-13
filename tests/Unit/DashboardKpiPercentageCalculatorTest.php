<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardKpiComparisonState;
use App\Analytics\Dashboards\DashboardKpiComparisonValue;
use App\Analytics\Dashboards\DashboardKpiPercentageCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class DashboardKpiPercentageCalculatorTest extends TestCase
{
    public function test_increase_is_calculated(): void
    {
        $percentage = (new DashboardKpiPercentageCalculator)
            ->calculate(
                $this->comparison(
                    current: '1500.00',
                    previous: '1200.00',
                ),
            );

        $this->assertSame('25.00', $percentage);
    }

    public function test_decrease_is_calculated(): void
    {
        $percentage = (new DashboardKpiPercentageCalculator)
            ->calculate(
                $this->comparison(
                    current: '800.00',
                    previous: '1000.00',
                ),
            );

        $this->assertSame('-20.00', $percentage);
    }

    public function test_percentage_is_rounded_to_two_decimals(): void
    {
        $percentage = (new DashboardKpiPercentageCalculator)
            ->calculate(
                $this->comparison(
                    current: 2,
                    previous: 3,
                ),
            );

        $this->assertSame('-33.33', $percentage);
    }

    #[DataProvider('nonComparableStates')]
    public function test_non_comparable_state_has_no_percentage(
        DashboardKpiComparisonState $state,
    ): void {
        $percentage = (new DashboardKpiPercentageCalculator)
            ->calculate(
                new DashboardKpiComparisonValue(
                    context: [],
                    current: 10,
                    previous: 0,
                    state: $state,
                ),
            );

        $this->assertNull($percentage);
    }

    /**
     * @return iterable<string, array{
     *     DashboardKpiComparisonState
     * }>
     */
    public static function nonComparableStates(): iterable
    {
        yield 'missing current' => [
            DashboardKpiComparisonState::MISSING_CURRENT,
        ];

        yield 'missing previous' => [
            DashboardKpiComparisonState::MISSING_PREVIOUS,
        ];

        yield 'previous zero' => [
            DashboardKpiComparisonState::PREVIOUS_ZERO,
        ];
    }

    private function comparison(
        int|string $current,
        int|string $previous,
    ): DashboardKpiComparisonValue {
        return new DashboardKpiComparisonValue(
            context: [],
            current: $current,
            previous: $previous,
            state: DashboardKpiComparisonState::COMPARABLE,
        );
    }
}
