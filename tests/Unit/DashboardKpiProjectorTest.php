<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardKpiProjector;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Queries\DatasetQuery;
use LogicException;
use Tests\TestCase;

final class DashboardKpiProjectorTest extends TestCase
{
    public function test_count_measure_is_projected_as_scalar(): void
    {
        $values = app(DashboardKpiProjector::class)
            ->project(
                query: new DatasetQuery(
                    dataset: DatasetKey::TRANSACTIONS,
                    measures: ['transaction_count'],
                ),
                rows: [
                    ['transaction_count' => 42],
                ],
            );

        $this->assertCount(1, $values);

        $this->assertSame(
            [
                'context' => [],
                'value' => 42,
            ],
            $values[0]->toArray(),
        );
    }

    public function test_currency_measure_preserves_separate_values(): void
    {
        $values = app(DashboardKpiProjector::class)
            ->project(
                query: new DatasetQuery(
                    dataset: DatasetKey::TRANSACTIONS,
                    dimensions: ['currency'],
                    measures: ['total_amount'],
                ),
                rows: [
                    [
                        'currency' => 'EUR',
                        'total_amount' => '1500.25',
                    ],
                    [
                        'currency' => 'CHF',
                        'total_amount' => '820.10',
                    ],
                ],
            );

        $this->assertSame(
            [
                [
                    'context' => ['currency' => 'EUR'],
                    'value' => '1500.25',
                ],
                [
                    'context' => ['currency' => 'CHF'],
                    'value' => '820.10',
                ],
            ],
            array_map(
                static fn ($value): array => $value->toArray(),
                $values,
            ),
        );
    }

    public function test_unrelated_grouping_dimension_is_rejected(): void
    {
        $this->expectException(LogicException::class);

        $this->expectExceptionMessage(
            'KPI query dimensions do not match the measure context.',
        );

        app(DashboardKpiProjector::class)->project(
            query: new DatasetQuery(
                dataset: DatasetKey::TRANSACTIONS,
                dimensions: [
                    'branch',
                    'currency',
                ],
                measures: ['total_amount'],
            ),
            rows: [],
        );
    }
}
