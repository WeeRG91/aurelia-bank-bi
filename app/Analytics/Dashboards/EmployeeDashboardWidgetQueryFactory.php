<?php

namespace App\Analytics\Dashboards;

use App\Analytics\Queries\DatasetQuery;
use App\Analytics\Queries\DatasetQueryComparison;
use App\Analytics\Queries\ReportDefinitionComparisonQueryFactory;
use App\Analytics\Queries\ReportDefinitionQueryFactory;
use App\Analytics\Time\ReportingTimezone;
use App\Models\DashboardWidget;
use App\Models\SavedReport;
use DateInvalidTimeZoneException;
use DateTimeImmutable;
use LogicException;

final readonly class EmployeeDashboardWidgetQueryFactory
{
    public function __construct(
        private ReportDefinitionQueryFactory $queryFactory,
        private ReportDefinitionComparisonQueryFactory $comparisonQueryFactory,
    ) {}

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function create(
        DashboardWidget $widget,
        DateTimeImmutable $now,
        ReportingTimezone $reportingTimezone,
    ): DatasetQuery {
        $report = $widget->savedReport;

        if (! $report instanceof SavedReport) {
            throw new LogicException(
                'The dashboard widget has no available saved report.',
            );
        }

        $definition = $report->definition;

        if (! is_array($definition)) {
            throw new LogicException(
                'The dashboard widget report definition is invalid.',
            );
        }

        $type = $widget->widget_type;

        if (! $type instanceof DashboardWidgetType) {
            throw new LogicException(
                'The dashboard widget type is invalid.',
            );
        }

        $configuration = DashboardWidgetConfiguration::from(
            $type,
            $widget->configuration,
        );

        if ($type === DashboardWidgetType::TABLE) {
            $values = $configuration->toArray();

            $definition['limit'] = $values['row_limit'];
        }

        return $this->queryFactory->create(
            dataset: $report->dataset,
            definition: $definition,
            now: $now,
            reportingTimezone: $reportingTimezone,
        );
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function createComparison(
        DashboardWidget $widget,
        DateTimeImmutable $now,
        ReportingTimezone $reportingTimezone,
    ): ?DatasetQueryComparison {
        $report = $widget->savedReport;

        if (! $report instanceof SavedReport) {
            throw new LogicException(
                'The dashboard widget has no available saved report.',
            );
        }

        $definition = $report->definition;

        if (! is_array($definition)) {
            throw new LogicException(
                'The dashboard widget report definition is invalid.',
            );
        }

        $type = $widget->widget_type;

        if (! $type instanceof DashboardWidgetType) {
            throw new LogicException(
                'The dashboard widget type is invalid.',
            );
        }

        if ($type !== DashboardWidgetType::KPI) {
            return null;
        }

        $configuration = DashboardWidgetConfiguration::from(
            $type,
            $widget->configuration,
        )->toArray();

        if (
            ($configuration['show_comparison'] ?? false)
            !== true
        ) {
            return null;
        }

        return $this->comparisonQueryFactory->create(
            dataset: $report->dataset,
            definition: $definition,
            now: $now,
            reportingTimezone: $reportingTimezone,
        );
    }
}
