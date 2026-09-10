<?php

namespace App\Analytics\Dashboards;

use App\Analytics\Time\RelativeDatePreset;

final readonly class DashboardWidgetResult
{
    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function __construct(
        public DashboardWidgetDefinition $widget,
        public RelativeDatePreset $period,
        public array $rows,
    ) {}

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     dataset: string,
     *     period: string,
     *     rows: list<array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->widget->key->value,
            'label' => $this->widget->label,
            'description' => $this->widget->description,
            'dataset' => $this->widget->dataset->value,
            'period' => $this->period->value,
            'rows' => $this->rows,
        ];
    }
}
