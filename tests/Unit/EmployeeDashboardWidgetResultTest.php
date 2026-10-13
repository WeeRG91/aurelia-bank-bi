<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\EmployeeDashboardWidgetResult;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Queries\DatasetQuery;
use App\Models\DashboardWidget;
use Tests\TestCase;

final class EmployeeDashboardWidgetResultTest extends TestCase
{
    public function test_available_result_contains_governed_query_metadata(): void
    {
        $widget = $this->widget(
            id: 42,
            type: 'table',
        );

        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['currency'],
            measures: ['total_amount'],
            limit: 25,
        );

        $result = EmployeeDashboardWidgetResult::available(
            widget: $widget,
            query: $query,
            rows: [
                [
                    'currency' => 'EUR',
                    'total_amount' => '1500.00',
                ],
            ],
        );

        $this->assertSame(
            [
                'widgetId' => 42,
                'type' => 'table',
                'status' => 'available',
                'dataset' => 'transactions',
                'dimensions' => ['currency'],
                'measures' => ['total_amount'],
                'visualization' => null,
                'rowCount' => 1,
                'rows' => [
                    [
                        'currency' => 'EUR',
                        'total_amount' => '1500.00',
                    ],
                ],
                'valueCount' => 0,
                'values' => [],
                'comparisonCount' => 0,
                'comparisons' => [],
            ],
            $result->toArray(),
        );
    }

    public function test_available_chart_result_contains_controlled_visualization_metadata(): void
    {
        $widget = $this->widget(
            id: 63,
            type: 'chart',
        );

        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['booking_date'],
            measures: ['total_amount'],
            limit: 100,
        );

        $result = EmployeeDashboardWidgetResult::available(
            widget: $widget,
            query: $query,
            rows: [],
            visualization: [
                'type' => 'line',
                'dimension' => 'booking_date',
                'measure' => 'total_amount',
                'series' => null,
            ],
        );

        $this->assertSame(
            [
                'type' => 'line',
                'dimension' => 'booking_date',
                'measure' => 'total_amount',
                'series' => null,
            ],
            $result->toArray()['visualization'],
        );
    }

    public function test_unavailable_result_exposes_no_report_data(): void
    {
        $result = EmployeeDashboardWidgetResult::unavailable(
            $this->widget(
                id: 84,
                type: 'kpi',
            ),
        );

        $this->assertSame(
            [
                'widgetId' => 84,
                'type' => 'kpi',
                'status' => 'unavailable',
                'dataset' => null,
                'dimensions' => [],
                'measures' => [],
                'visualization' => null,
                'rowCount' => 0,
                'rows' => [],
                'valueCount' => 0,
                'values' => [],
                'comparisonCount' => 0,
                'comparisons' => [],
            ],
            $result->toArray(),
        );
    }

    private function widget(
        int $id,
        string $type,
    ): DashboardWidget {
        $widget = new DashboardWidget([
            'widget_type' => $type,
            'configuration' => null,
        ]);

        $widget->setAttribute('id', $id);

        return $widget;
    }
}
