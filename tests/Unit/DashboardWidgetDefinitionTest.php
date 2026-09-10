<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardWidgetDefinition;
use App\Analytics\Dashboards\DashboardWidgetKey;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Time\RelativeDatePreset;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DashboardWidgetDefinitionTest extends TestCase
{
    public function test_widget_produces_a_canonical_report_definition(): void
    {
        $widget = $this->widget();

        $this->assertSame(
            [
                'dimensions' => ['currency'],
                'measures' => ['transaction_count'],
                'filters' => [],
                'relative_date' => [
                    'dimension' => 'booking_date',
                    'preset' => 'last_30_days',
                ],
                'limit' => 20,
            ],
            $widget->reportDefinition(),
        );
    }

    public function test_widget_requires_a_dimension_or_measure(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DashboardWidgetDefinition(
            key: DashboardWidgetKey::TRANSACTION_SUMMARY,
            label: 'Transaction summary',
            description: 'Transaction activity by currency.',
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: [],
            measures: [],
            relativeDateDimension: 'booking_date',
            relativeDatePreset: RelativeDatePreset::LAST_30_DAYS,
            limit: 20,
        );
    }

    public function test_widget_rejects_an_invalid_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DashboardWidgetDefinition(
            key: DashboardWidgetKey::TRANSACTION_SUMMARY,
            label: 'Transaction summary',
            description: 'Transaction activity by currency.',
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['currency'],
            measures: ['transaction_count'],
            relativeDateDimension: 'booking_date',
            relativeDatePreset: RelativeDatePreset::LAST_30_DAYS,
            limit: 501,
        );
    }

    public function test_reporting_period_can_be_overridden(): void
    {
        $definition = $this->widget()->reportDefinition(
            RelativeDatePreset::YEAR_TO_DATE,
        );

        $this->assertSame(
            'year_to_date',
            $definition['relative_date']['preset'],
        );
    }

    private function widget(): DashboardWidgetDefinition
    {
        return new DashboardWidgetDefinition(
            key: DashboardWidgetKey::TRANSACTION_SUMMARY,
            label: 'Transaction summary',
            description: 'Transaction activity by currency.',
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['currency'],
            measures: ['transaction_count'],
            relativeDateDimension: 'booking_date',
            relativeDatePreset: RelativeDatePreset::LAST_30_DAYS,
            limit: 20,
        );
    }
}
