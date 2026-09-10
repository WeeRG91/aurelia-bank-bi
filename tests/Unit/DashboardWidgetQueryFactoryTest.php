<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardWidgetKey;
use App\Analytics\Dashboards\DashboardWidgetQueryFactory;
use App\Analytics\Dashboards\DashboardWidgetRegistry;
use App\Analytics\Filters\FilterOperator;
use App\Analytics\Queries\Authorization\DatasetRowScope;
use App\Analytics\Queries\DatasetQueryCompiler;
use App\Analytics\Queries\Sources\TransactionDatasetSource;
use App\Analytics\Time\RelativeDatePreset;
use App\Analytics\Time\ReportingTimezone;
use DateInvalidTimeZoneException;
use DateTimeImmutable;
use Tests\TestCase;

final class DashboardWidgetQueryFactoryTest extends TestCase
{
    /**
     * @throws DateInvalidTimeZoneException
     */
    public function test_widget_is_converted_to_a_relative_date_query(): void
    {
        $widget = (new DashboardWidgetRegistry)->get(
            DashboardWidgetKey::DAILY_CASH_FLOW,
        );

        $query = app(DashboardWidgetQueryFactory::class)->create(
            widget: $widget,
            now: new DateTimeImmutable(
                '2026-09-09T12:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $this->assertSame($widget->dataset, $query->dataset);
        $this->assertSame($widget->dimensions, $query->dimensions);
        $this->assertSame($widget->measures, $query->measures);
        $this->assertSame($widget->limit, $query->limit);

        $this->assertCount(1, $query->filters);
        $this->assertSame(
            'booking_date',
            $query->filters[0]->dimension,
        );
        $this->assertSame(
            FilterOperator::BETWEEN,
            $query->filters[0]->operator,
        );
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function test_every_registered_widget_compiles_safely(): void
    {
        $registry = new DashboardWidgetRegistry;
        $factory = app(DashboardWidgetQueryFactory::class);
        $compiler = app(DatasetQueryCompiler::class);

        foreach ($registry->all() as $widget) {
            $query = $factory->create(
                widget: $widget,
                now: new DateTimeImmutable(
                    '2026-09-09T12:00:00+02:00',
                ),
                reportingTimezone: new ReportingTimezone(
                    'Europe/Luxembourg',
                ),
            );

            $compiled = $compiler->compile(
                new TransactionDatasetSource,
                $query,
                DatasetRowScope::unrestricted(),
            );

            $this->assertStringContainsString(
                'transactions',
                $compiled->sql,
            );

            $this->assertStringContainsString(
                'transactions.booked_at',
                $compiled->sql,
            );

            $this->assertSame(
                $widget->limit,
                $compiled->bindings[array_key_last(
                    $compiled->bindings,
                )],
            );
        }
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function test_selected_period_overrides_widget_default(): void
    {
        $widget = (new DashboardWidgetRegistry)->get(
            DashboardWidgetKey::TRANSACTION_SUMMARY,
        );

        $query = app(DashboardWidgetQueryFactory::class)->create(
            widget: $widget,
            now: new DateTimeImmutable(
                '2026-09-09T12:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
            relativeDatePreset: RelativeDatePreset::LAST_7_DAYS,
        );

        $this->assertCount(1, $query->filters);

        $this->assertSame(
            FilterOperator::BETWEEN,
            $query->filters[0]->operator,
        );

        $this->assertSame(
            [
                '2026-09-03',
                '2026-09-09',
            ],
            $query->filters[0]->value,
        );
    }
}
