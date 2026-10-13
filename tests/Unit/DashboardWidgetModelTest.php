<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardWidgetType;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\SavedReport;
use Tests\TestCase;

final class DashboardWidgetModelTest extends TestCase
{
    public function test_widget_casts_and_relationships_are_configured(): void
    {
        $widget = new DashboardWidget;

        $widget->forceFill([
            'widget_type' => 'kpi',
            'grid_column' => '6',
            'grid_row' => '2',
            'grid_width' => '6',
            'grid_height' => '3',
            'configuration' => [
                'show_comparison' => true,
            ],
        ]);

        $this->assertSame(
            DashboardWidgetType::KPI,
            $widget->widget_type,
        );

        $this->assertSame(
            [
                'show_comparison' => true,
            ],
            $widget->configuration,
        );

        $this->assertSame(
            [
                'column' => 6,
                'row' => 2,
                'width' => 6,
                'height' => 3,
            ],
            $widget->layout()->toArray(),
        );

        $this->assertInstanceOf(
            Dashboard::class,
            $widget->dashboard()->getRelated(),
        );

        $this->assertInstanceOf(
            SavedReport::class,
            $widget->savedReport()->getRelated(),
        );

        $this->assertSame(
            'dashboard_id',
            $widget->dashboard()->getForeignKeyName(),
        );

        $this->assertSame(
            'saved_report_id',
            $widget->savedReport()->getForeignKeyName(),
        );
    }
}
