<?php

namespace App\Http\Requests\Analytics;

use App\Analytics\Dashboards\DashboardWidgetConfiguration;
use App\Analytics\Dashboards\DashboardWidgetLayout;
use App\Analytics\Dashboards\DashboardWidgetType;
use App\Analytics\Datasets\DatasetRegistry;
use App\Models\SavedReport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use LogicException;

abstract class DashboardWidgetRequest extends FormRequest
{
    private ?DashboardWidgetConfiguration $widgetConfiguration = null;

    private ?DashboardWidgetLayout $widgetLayout = null;

    private ?SavedReport $authorizedSavedReport = null;

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'saved_report_id' => [
                'required',
                'integer',
                Rule::exists('saved_reports', 'id')
                    ->whereNull('deleted_at'),
            ],
            'title' => [
                'required',
                'string',
                'max:150',
                'regex:/\S/',
            ],
            'widget_type' => [
                'required',
                Rule::enum(DashboardWidgetType::class),
            ],
            'grid_column' => [
                'required',
                'integer',
                'between:0,11',
            ],
            'grid_row' => [
                'required',
                'integer',
                'min:0',
            ],
            'grid_width' => [
                'required',
                'integer',
                'between:1,12',
            ],
            'grid_height' => [
                'required',
                'integer',
                'between:1,12',
            ],
            'configuration' => [
                'nullable',
                'array:show_comparison,row_limit',
            ],
            'configuration.show_comparison' => [
                'sometimes',
                'boolean',
                'prohibited_unless:widget_type,kpi',
            ],
            'configuration.row_limit' => [
                'sometimes',
                'integer',
                'between:1,100',
                'prohibited_unless:widget_type,table',
            ],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateDomainRules($validator);
            },
        ];
    }

    /**
     * @return array{
     *     saved_report_id: int,
     *     title: string,
     *     widget_type: DashboardWidgetType,
     *     grid_column: int,
     *     grid_row: int,
     *     grid_width: int,
     *     grid_height: int,
     *     configuration: array<string, bool|int>|null
     * }
     */
    public function toWidgetAttributes(): array
    {
        if (
            $this->widgetConfiguration === null
            || $this->widgetLayout === null
            || $this->authorizedSavedReport === null
        ) {
            throw new LogicException(
                'Dashboard widget request must be validated before use.',
            );
        }

        $layout = $this->widgetLayout->toArray();

        return [
            'saved_report_id' => (int) $this
                ->authorizedSavedReport
                ->getKey(),
            'title' => trim(
                (string) $this->validated('title'),
            ),
            'widget_type' => DashboardWidgetType::from(
                (string) $this->validated('widget_type'),
            ),
            'grid_column' => $layout['column'],
            'grid_row' => $layout['row'],
            'grid_width' => $layout['width'],
            'grid_height' => $layout['height'],
            'configuration' => $this
                ->widgetConfiguration
                ->toArray(),
        ];
    }

    public function authorizedSavedReport(): SavedReport
    {
        if ($this->authorizedSavedReport === null) {
            throw new LogicException(
                'Saved report authorization has not completed.',
            );
        }

        return $this->authorizedSavedReport;
    }

    private function validateDomainRules(
        Validator $validator,
    ): void {
        if ($validator->errors()->hasAny([
            'saved_report_id',
            'title',
            'widget_type',
            'grid_column',
            'grid_row',
            'grid_width',
            'grid_height',
            'configuration',
            'configuration.show_comparison',
            'configuration.row_limit',
        ])) {
            return;
        }

        $type = DashboardWidgetType::from(
            (string) $this->input('widget_type'),
        );

        try {
            $this->widgetLayout = new DashboardWidgetLayout(
                column: $this->integer('grid_column'),
                row: $this->integer('grid_row'),
                width: $this->integer('grid_width'),
                height: $this->integer('grid_height'),
            );
        } catch (InvalidArgumentException $exception) {
            $validator->errors()->add(
                'layout',
                $exception->getMessage(),
            );

            return;
        }

        try {
            $this->widgetConfiguration =
                DashboardWidgetConfiguration::from(
                    $type,
                    $this->normalizedConfiguration(),
                );
        } catch (InvalidArgumentException $exception) {
            $validator->errors()->add(
                'configuration',
                $exception->getMessage(),
            );

            return;
        }

        $savedReport = SavedReport::query()->find(
            $this->integer('saved_report_id'),
        );

        if (
            $savedReport === null
            || $this->user() === null
            || Gate::forUser($this->user())
                ->denies('export', $savedReport)
        ) {
            $validator->errors()->add(
                'saved_report_id',
                'The selected report cannot be used in this dashboard.',
            );

            return;
        }

        if (! $this->supportsWidgetType($savedReport, $type)) {
            $validator->errors()->add(
                'widget_type',
                'The selected report is not compatible with this widget type.',
            );

            return;
        }

        $this->authorizedSavedReport = $savedReport;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizedConfiguration(): ?array
    {
        $configuration = $this->input('configuration');

        if (! is_array($configuration)) {
            return null;
        }

        $normalized = [];

        if (array_key_exists(
            'show_comparison',
            $configuration,
        )) {
            $normalized['show_comparison'] = $this->boolean(
                'configuration.show_comparison',
            );
        }

        if (array_key_exists('row_limit', $configuration)) {
            $normalized['row_limit'] = (int) $configuration[
            'row_limit'
            ];
        }

        return $normalized;
    }

    private function supportsWidgetType(
        SavedReport $savedReport,
        DashboardWidgetType $type,
    ): bool {
        $definition = $savedReport->definition;

        if (! is_array($definition)) {
            return false;
        }

        if ($type === DashboardWidgetType::KPI) {
            $measures = $definition['measures'] ?? null;
            $dimensions = $definition['dimensions'] ?? null;

            if (
                ! is_array($measures)
                || count($measures) !== 1
                || ! is_string($measures[0])
                || ! is_array($dimensions)
            ) {
                return false;
            }

            $dataset = app(DatasetRegistry::class)
                ->find($savedReport->dataset);

            $measure = $dataset?->findMeasure(
                $measures[0],
            );

            if ($measure === null) {
                return false;
            }

            $requiredDimensions = $measure
                ->requiredContextDimensions();

            sort($dimensions);
            sort($requiredDimensions);

            if ($dimensions !== $requiredDimensions) {
                return false;
            }

            $configuration = $this
                ->widgetConfiguration
                ?->toArray();

            if (
                ($configuration['show_comparison'] ?? false)
                !== true
            ) {
                return true;
            }

            $relativeDate = $definition['relative_date']
                ?? null;

            return is_array($relativeDate)
                && is_string(
                    $relativeDate['dimension'] ?? null,
                )
                && is_string(
                    $relativeDate['preset'] ?? null,
                );
        }

        if ($type === DashboardWidgetType::CHART) {
            $visualization = $definition['visualization']
                ?? null;

            return is_array($visualization)
                && in_array(
                    $visualization['type'] ?? null,
                    ['line', 'bar'],
                    true,
                );
        }

        return true;
    }
}
