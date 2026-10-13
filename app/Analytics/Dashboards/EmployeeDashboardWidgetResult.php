<?php

namespace App\Analytics\Dashboards;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Queries\DatasetQuery;
use App\Models\DashboardWidget;

final readonly class EmployeeDashboardWidgetResult
{
    /**
     * @param  list<string>  $dimensions
     * @param  list<string>  $measures
     * @param  array{
     *     type: string,
     *     dimension: string,
     *     measure: string,
     *     series: string|null
     * }|null  $visualization
     * @param  list<array<string, mixed>>  $rows
     * @param  list<DashboardKpiValue>  $values
     * @param  list<DashboardKpiComparisonResult>  $comparisons
     */
    private function __construct(
        public int $widgetId,
        public DashboardWidgetType $type,
        public DashboardWidgetExecutionStatus $status,
        public ?DatasetKey $dataset,
        public array $dimensions,
        public array $measures,
        public ?array $visualization,
        public array $rows,
        public array $values,
        public array $comparisons,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array{
     *     type: string,
     *     dimension: string,
     *     measure: string,
     *     series: string|null
     * }|null  $visualization
     */
    public static function available(
        DashboardWidget $widget,
        DatasetQuery $query,
        array $rows,
        ?array $visualization = null,
    ): self {
        return new self(
            widgetId: (int) $widget->getKey(),
            type: $widget->widget_type,
            status: DashboardWidgetExecutionStatus::AVAILABLE,
            dataset: $query->dataset,
            dimensions: $query->dimensions,
            measures: $query->measures,
            visualization: $visualization,
            rows: $rows,
            values: [],
            comparisons: [],
        );
    }

    /**
     * @param  list<DashboardKpiValue>  $values
     * @param  list<DashboardKpiComparisonResult>  $comparisons
     */
    public static function availableKpi(
        DashboardWidget $widget,
        DatasetQuery $query,
        array $values,
        array $comparisons = [],
    ): self {
        return new self(
            widgetId: (int) $widget->getKey(),
            type: $widget->widget_type,
            status: DashboardWidgetExecutionStatus::AVAILABLE,
            dataset: $query->dataset,
            dimensions: $query->dimensions,
            measures: $query->measures,
            visualization: null,
            rows: [],
            values: $values,
            comparisons: $comparisons,
        );
    }

    public static function unavailable(
        DashboardWidget $widget,
    ): self {
        return new self(
            widgetId: (int) $widget->getKey(),
            type: $widget->widget_type,
            status: DashboardWidgetExecutionStatus::UNAVAILABLE,
            dataset: null,
            dimensions: [],
            measures: [],
            visualization: null,
            rows: [],
            values: [],
            comparisons: [],
        );
    }

    /**
     * @return array{
     *     widgetId: int,
     *     type: string,
     *     status: string,
     *     dataset: string|null,
     *     dimensions: list<string>,
     *     measures: list<string>,
     *          visualization: array{
     *          type: string,
     *          dimension: string,
     *          measure: string,
     *          series: string|null
     *      }|null,
     *     rowCount: int,
     *     rows: list<array<string, mixed>>,
     *     valueCount: int,
     *     values: list<array{
     *         context: array<string, bool|int|string|null>,
     *         value: int|string|null
     *     }>,
     *     comparisonCount: int,
     *      comparisons: list<array{
     *          context: array<string, bool|int|string|null>,
     *          current: int|string|null,
     *          previous: int|string|null,
     *          state: string,
     *          percentage_change: string|null
     *      }>
     * }
     */
    public function toArray(): array
    {
        return [
            'widgetId' => $this->widgetId,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'dataset' => $this->dataset?->value,
            'dimensions' => $this->dimensions,
            'measures' => $this->measures,
            'visualization' => $this->visualization,
            'rowCount' => count($this->rows),
            'rows' => $this->rows,
            'valueCount' => count($this->values),
            'values' => array_map(
                static fn (
                    DashboardKpiValue $value,
                ): array => $value->toArray(),
                $this->values,
            ),
            'comparisonCount' => count($this->comparisons),
            'comparisons' => array_map(
                static fn (
                    DashboardKpiComparisonResult $comparison,
                ): array => $comparison->toArray(),
                $this->comparisons,
            ),
        ];
    }
}
