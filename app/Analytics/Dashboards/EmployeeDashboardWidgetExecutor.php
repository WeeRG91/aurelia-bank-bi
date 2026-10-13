<?php

namespace App\Analytics\Dashboards;

use App\Analytics\Queries\AuthorizedDatasetQueryExecutor;
use App\Analytics\Queries\Sources\DatasetSourceRegistry;
use App\Analytics\Time\ReportingTimezone;
use App\Models\DashboardWidget;
use App\Models\SavedReport;
use App\Models\User;
use DateInvalidTimeZoneException;
use DateTimeImmutable;
use Illuminate\Contracts\Auth\Access\Gate;
use LogicException;
use Throwable;

final readonly class EmployeeDashboardWidgetExecutor
{
    public function __construct(
        private Gate $gate,
        private EmployeeDashboardWidgetQueryFactory $queryFactory,
        private DatasetSourceRegistry $sources,
        private AuthorizedDatasetQueryExecutor $executor,
        private DashboardKpiProjector $kpiProjector,
        private DashboardKpiComparisonProjector $kpiComparisonProjector,
    ) {}

    /**
     * @throws DateInvalidTimeZoneException
     * @throws Throwable
     */
    public function execute(
        User $user,
        DashboardWidget $widget,
        DateTimeImmutable $now,
        ReportingTimezone $reportingTimezone,
    ): EmployeeDashboardWidgetResult {
        $report = $widget->savedReport;

        if (
            ! $report instanceof SavedReport
            || ! $this->gate
                ->forUser($user)
                ->allows('export', $report)
        ) {
            return EmployeeDashboardWidgetResult::unavailable(
                $widget,
            );
        }

        $queryComparison = null;

        if ($widget->widget_type === DashboardWidgetType::KPI) {
            $queryComparison = $this->queryFactory
                ->createComparison(
                    widget: $widget,
                    now: $now,
                    reportingTimezone: $reportingTimezone,
                );
        }

        $query = $queryComparison?->current
            ?? $this->queryFactory->create(
                widget: $widget,
                now: $now,
                reportingTimezone: $reportingTimezone,
            );

        $rows = $this->executor->executeFor(
            user: $user,
            source: $this->sources->get($query->dataset),
            query: $query,
        );

        $normalizedRows = array_map(
            static fn (object $row): array => (array) $row,
            $rows,
        );

        if ($widget->widget_type === DashboardWidgetType::KPI) {
            $values = $this->kpiProjector->project(
                query: $query,
                rows: $normalizedRows,
            );

            $comparisons = [];

            if ($queryComparison !== null) {
                $comparisonRows = $this->executor->executeFor(
                    user: $user,
                    source: $this->sources->get(
                        $queryComparison->comparison->dataset,
                    ),
                    query: $queryComparison->comparison,
                );

                $normalizedComparisonRows = array_map(
                    static fn (object $row): array => (array) $row,
                    $comparisonRows,
                );

                $previousValues = $this->kpiProjector->project(
                    query: $queryComparison->comparison,
                    rows: $normalizedComparisonRows,
                );

                $comparisons = $this->kpiComparisonProjector
                    ->project(
                        current: $values,
                        previous: $previousValues,
                    );
            }

            return EmployeeDashboardWidgetResult::availableKpi(
                widget: $widget,
                query: $query,
                values: $values,
                comparisons: $comparisons,
            );
        }

        return EmployeeDashboardWidgetResult::available(
            widget: $widget,
            query: $query,
            rows: $normalizedRows,
            visualization: $widget->widget_type
            === DashboardWidgetType::CHART
                ? $this->chartVisualization($report)
                : null,
        );
    }

    /**
     * @return array{
     *     type: string,
     *     dimension: string,
     *     measure: string,
     *     series: string|null
     * }
     */
    private function chartVisualization(
        SavedReport $report,
    ): array {
        $definition = $report->definition;

        $visualization = is_array($definition)
            ? ($definition['visualization'] ?? null)
            : null;

        if (! is_array($visualization)) {
            throw new LogicException(
                'The chart saved report has no visualization configuration.',
            );
        }

        $type = $visualization['type'] ?? null;
        $dimension = $visualization['dimension'] ?? null;
        $measure = $visualization['measure'] ?? null;
        $series = $visualization['series'] ?? null;

        if (
            ! is_string($type)
            || ! in_array($type, ['bar', 'line'], true)
            || ! is_string($dimension)
            || $dimension === ''
            || ! is_string($measure)
            || $measure === ''
            || ($series !== null && ! is_string($series))
        ) {
            throw new LogicException(
                'The chart saved report has an invalid visualization configuration.',
            );
        }

        return [
            'type' => $type,
            'dimension' => $dimension,
            'measure' => $measure,
            'series' => $series,
        ];
    }
}
