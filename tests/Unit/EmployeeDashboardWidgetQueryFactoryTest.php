<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\EmployeeDashboardWidgetQueryFactory;
use App\Analytics\Time\ReportingTimezone;
use App\Models\DashboardWidget;
use App\Models\SavedReport;
use DateTimeImmutable;
use LogicException;
use Tests\TestCase;

final class EmployeeDashboardWidgetQueryFactoryTest extends TestCase
{
    public function test_saved_report_definition_is_rebuilt_as_query(): void
    {
        $widget = $this->widget(
            type: 'kpi',
            configuration: [
                'show_comparison' => false,
            ],
            definition: [
                'dimensions' => ['currency'],
                'measures' => ['total_amount'],
                'filters' => [],
                'relative_date' => null,
                'limit' => 75,
                'visualization' => null,
            ],
        );

        $query = app(
            EmployeeDashboardWidgetQueryFactory::class,
        )->create(
            widget: $widget,
            now: new DateTimeImmutable(
                '2026-10-13T10:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $this->assertSame(
            ['currency'],
            $query->dimensions,
        );

        $this->assertSame(
            ['total_amount'],
            $query->measures,
        );

        $this->assertSame(75, $query->limit);
    }

    public function test_table_configuration_controls_result_limit(): void
    {
        $widget = $this->widget(
            type: 'table',
            configuration: [
                'row_limit' => 25,
            ],
            definition: [
                'dimensions' => [
                    'booking_date',
                    'currency',
                ],
                'measures' => ['total_amount'],
                'filters' => [],
                'relative_date' => null,
                'limit' => 100,
                'visualization' => null,
            ],
        );

        $query = app(
            EmployeeDashboardWidgetQueryFactory::class,
        )->create(
            widget: $widget,
            now: new DateTimeImmutable(
                '2026-10-13T10:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $this->assertSame(25, $query->limit);

        $this->assertSame(
            ['booking_date', 'currency'],
            $query->dimensions,
        );

        $this->assertSame(
            ['total_amount'],
            $query->measures,
        );
    }

    public function test_missing_saved_report_is_rejected(): void
    {
        $widget = new DashboardWidget([
            'widget_type' => 'kpi',
            'configuration' => [
                'show_comparison' => false,
            ],
        ]);

        $widget->setRelation('savedReport', null);

        $this->expectException(LogicException::class);

        $this->expectExceptionMessage(
            'The dashboard widget has no available saved report.',
        );

        app(
            EmployeeDashboardWidgetQueryFactory::class,
        )->create(
            widget: $widget,
            now: new DateTimeImmutable(
                '2026-10-13T10:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );
    }

    public function test_comparison_enabled_kpi_builds_previous_period_pair(): void
    {
        $widget = $this->widget(
            type: 'kpi',
            configuration: [
                'show_comparison' => true,
            ],
            definition: [
                'dimensions' => ['currency'],
                'measures' => ['total_amount'],
                'filters' => [],
                'relative_date' => [
                    'dimension' => 'booked_at',
                    'preset' => 'last_30_days',
                ],
                'limit' => 100,
                'visualization' => null,
            ],
        );

        $pair = app(
            EmployeeDashboardWidgetQueryFactory::class,
        )->createComparison(
            widget: $widget,
            now: new DateTimeImmutable(
                '2026-10-13T10:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $this->assertNotNull($pair);

        $this->assertSame(
            '2026-09-14',
            $pair->currentRange->startDate,
        );

        $this->assertSame(
            '2026-10-13',
            $pair->currentRange->endDate,
        );

        $this->assertSame(
            '2026-08-15',
            $pair->comparisonRange->startDate,
        );

        $this->assertSame(
            '2026-09-13',
            $pair->comparisonRange->endDate,
        );

        $this->assertSame(
            $pair->current->dimensions,
            $pair->comparison->dimensions,
        );

        $this->assertSame(
            $pair->current->measures,
            $pair->comparison->measures,
        );
    }

    public function test_comparison_disabled_kpi_has_no_query_pair(): void
    {
        $widget = $this->widget(
            type: 'kpi',
            configuration: [
                'show_comparison' => false,
            ],
            definition: [
                'dimensions' => ['currency'],
                'measures' => ['total_amount'],
                'filters' => [],
                'relative_date' => null,
                'limit' => 100,
                'visualization' => null,
            ],
        );

        $pair = app(
            EmployeeDashboardWidgetQueryFactory::class,
        )->createComparison(
            widget: $widget,
            now: new DateTimeImmutable(
                '2026-10-13T10:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $this->assertNull($pair);
    }

    /**
     * @param  array<string, bool|int>|null  $configuration
     * @param  array<string, mixed>  $definition
     */
    private function widget(
        string $type,
        ?array $configuration,
        array $definition,
    ): DashboardWidget {
        $report = new SavedReport([
            'owner_employee_id' => 10,
            'name' => 'Governed report',
            'dataset' => 'transactions',
            'definition_version' => 1,
            'definition' => $definition,
        ]);

        $widget = new DashboardWidget([
            'dashboard_id' => 20,
            'saved_report_id' => 30,
            'title' => 'Dashboard widget',
            'widget_type' => $type,
            'grid_column' => 0,
            'grid_row' => 0,
            'grid_width' => 4,
            'grid_height' => 3,
            'configuration' => $configuration,
        ]);

        $widget->setRelation('savedReport', $report);

        return $widget;
    }
}
