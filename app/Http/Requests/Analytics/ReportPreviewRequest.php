<?php

namespace App\Http\Requests\Analytics;

use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\FieldDataType;
use App\Analytics\Filters\FilterCondition;
use App\Analytics\Filters\FilterOperator;
use App\Analytics\Filters\FilterValidator;
use App\Analytics\Filters\InvalidFilter;
use App\Analytics\Queries\DatasetQuery;
use App\Analytics\Time\RelativeDateDatasetQueryFactory;
use App\Analytics\Time\RelativeDatePreset;
use App\Analytics\Time\ReportingTimezone;
use App\Analytics\Visualizations\ChartType;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateInvalidTimeZoneException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReportPreviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $dataset = $this->input('dataset');

        return $user instanceof User
            && is_string($dataset)
            && app(DatasetAccess::class)->canUse(
                $user,
                $dataset,
            );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'dataset' => [
                'required',
                'string',
                Rule::enum(DatasetKey::class),
            ],
            'dimensions' => [
                'present',
                'array',
                'max:20',
            ],
            'dimensions.*' => [
                'string',
                'regex:/^[a-z][a-z0-9_]*$/',
                'distinct:strict',
            ],
            'measures' => [
                'present',
                'array',
                'max:10',
            ],
            'measures.*' => [
                'string',
                'regex:/^[a-z][a-z0-9_]*$/',
                'distinct:strict',
            ],
            'filters' => [
                'sometimes',
                'array',
                'list',
                'max:20',
            ],
            'filters.*' => [
                'array:dimension,operator,value',
            ],
            'filters.*.dimension' => [
                'required',
                'string',
                'regex:/^[a-z][a-z0-9_]*$/',
            ],
            'filters.*.operator' => [
                'required',
                'string',
                Rule::enum(FilterOperator::class),
            ],
            'filters.*.value' => [
                'present',
            ],
            'limit' => [
                'sometimes',
                'integer',
                'between:1,500',
            ],
            'relative_date' => [
                'sometimes',
                'nullable',
                'array:dimension,preset',
            ],
            'relative_date.dimension' => [
                'required_with:relative_date',
                'string',
                'regex:/^[a-z][a-z0-9_]*$/',
            ],
            'relative_date.preset' => [
                'required_with:relative_date',
                'string',
                Rule::enum(RelativeDatePreset::class),
            ],
            'visualization' => [
                'sometimes',
                'nullable',
                'array:type,dimension,measure,series',
            ],
            'visualization.type' => [
                'required_with:visualization',
                'string',
                Rule::enum(ChartType::class),
            ],
            'visualization.dimension' => [
                'required_with:visualization',
                'string',
                'regex:/^[a-z][a-z0-9_]*$/',
            ],
            'visualization.measure' => [
                'required_with:visualization',
                'string',
                'regex:/^[a-z][a-z0-9_]*$/',
            ],
            'visualization.series' => [
                'nullable',
                'string',
                'regex:/^[a-z][a-z0-9_]*$/',
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
                $dimensions = $this->input('dimensions');
                $measures = $this->input('measures');

                if (! is_array($dimensions) || ! is_array($measures)) {
                    return;
                }

                if ($dimensions === [] && $measures === []) {
                    $validator->errors()->add(
                        'dimensions',
                        'At least one dimension or measure is required.',
                    );

                    return;
                }

                $datasetIdentifier = $this->input('dataset');

                if (! is_string($datasetIdentifier)) {
                    return;
                }

                $dataset = app(DatasetRegistry::class)
                    ->find($datasetIdentifier);

                if ($dataset === null) {
                    return;
                }

                foreach ($dimensions as $index => $dimensionKey) {
                    if (
                        is_string($dimensionKey)
                        && $dataset->findDimension($dimensionKey) === null
                    ) {
                        $validator->errors()->add(
                            "dimensions.{$index}",
                            "Unknown dimension [{$dimensionKey}] for dataset [{$datasetIdentifier}].",
                        );
                    }
                }

                foreach ($measures as $index => $measureKey) {
                    if (! is_string($measureKey)) {
                        continue;
                    }

                    $measure = $dataset->findMeasure($measureKey);

                    if ($measure === null) {
                        $validator->errors()->add(
                            "measures.{$index}",
                            "Unknown measure [{$measureKey}] for dataset [{$datasetIdentifier}].",
                        );

                        continue;
                    }

                    foreach (
                        $measure->requiredContextDimensions() as $requiredDimension
                    ) {
                        if (
                            ! in_array(
                                $requiredDimension,
                                $dimensions,
                                true,
                            )
                        ) {
                            $validator->errors()->add(
                                "measures.{$index}",
                                "Measure [{$measureKey}] requires dimension [{$requiredDimension}].",
                            );
                        }
                    }
                }

                $filters = $this->input('filters', []);

                if (! is_array($filters)) {
                    return;
                }

                $filterValidator = app(FilterValidator::class);

                foreach ($filters as $index => $filter) {
                    if (
                        ! is_array($filter)
                        || ! isset($filter['dimension'])
                        || ! is_string($filter['dimension'])
                        || ! isset($filter['operator'])
                        || ! is_string($filter['operator'])
                        || ! array_key_exists('value', $filter)
                    ) {
                        continue;
                    }

                    try {
                        $filterValidator->validate(
                            $dataset->key,
                            $filter['dimension'],
                            $filter['operator'],
                            $filter['value'],
                        );
                    } catch (InvalidFilter $exception) {
                        $validator->errors()->add(
                            "filters.{$index}",
                            $exception->getMessage(),
                        );
                    }
                }

                $visualization = $this->input('visualization');

                if (is_array($visualization)) {
                    $visualizationDimension =
                        $visualization['dimension'] ?? null;

                    $visualizationMeasure =
                        $visualization['measure'] ?? null;

                    $visualizationSeries =
                        $visualization['series'] ?? null;

                    $visualizationType = isset($visualization['type'])
                    && is_string($visualization['type'])
                        ? ChartType::tryFrom($visualization['type'])
                        : null;

                    if (
                        is_string($visualizationDimension)
                        && ! in_array(
                            $visualizationDimension,
                            $dimensions,
                            true,
                        )
                    ) {
                        $validator->errors()->add(
                            'visualization.dimension',
                            'The chart dimension must be selected in the report.',
                        );
                    }

                    if (
                        is_string($visualizationMeasure)
                        && ! in_array(
                            $visualizationMeasure,
                            $measures,
                            true,
                        )
                    ) {
                        $validator->errors()->add(
                            'visualization.measure',
                            'The chart measure must be selected in the report.',
                        );
                    }

                    if (
                        is_string($visualizationSeries)
                        && ! in_array(
                            $visualizationSeries,
                            $dimensions,
                            true,
                        )
                    ) {
                        $validator->errors()->add(
                            'visualization.series',
                            'The chart series must be selected in the report.',
                        );
                    }

                    if (
                        is_string($visualizationSeries)
                        && is_string($visualizationDimension)
                        && $visualizationSeries === $visualizationDimension
                    ) {
                        $validator->errors()->add(
                            'visualization.series',
                            'The chart series must differ from the horizontal axis.',
                        );
                    }

                    if (
                        $visualizationType === ChartType::LINE
                        && is_string($visualizationDimension)
                    ) {
                        $chartDimension = $dataset->findDimension(
                            $visualizationDimension,
                        );

                        if (
                            $chartDimension !== null
                            && ! in_array(
                                $chartDimension->dataType,
                                [
                                    FieldDataType::DATE,
                                    FieldDataType::DATETIME,
                                ],
                                true,
                            )
                        ) {
                            $validator->errors()->add(
                                'visualization.dimension',
                                'Line charts require a temporal dimension.',
                            );
                        }
                    }
                }

                $relativeDate = $this->input('relative_date');

                if ($relativeDate === null) {
                    return;
                }

                if (
                    ! is_array($relativeDate)
                    || ! isset($relativeDate['dimension'])
                    || ! is_string($relativeDate['dimension'])
                ) {
                    return;
                }

                $relativeDimension = $dataset->findDimension(
                    $relativeDate['dimension'],
                );

                if ($relativeDimension === null) {
                    $validator->errors()->add(
                        'relative_date.dimension',
                        "Unknown relative date dimension [{$relativeDate['dimension']}] for dataset [{$dataset->key->value}].",
                    );

                    return;
                }

                if (
                    ! in_array(
                        $relativeDimension->dataType,
                        [
                            FieldDataType::DATE,
                            FieldDataType::DATETIME,
                        ],
                        true,
                    )
                ) {
                    $validator->errors()->add(
                        'relative_date.dimension',
                        "Relative date dimension [{$relativeDimension->key}] must be a date or datetime.",
                    );
                }

                foreach ($filters as $filter) {
                    if (
                        is_array($filter)
                        && ($filter['dimension'] ?? null) === $relativeDimension->key
                    ) {
                        $validator->errors()->add(
                            'relative_date.dimension',
                            "Explicit filter for [{$relativeDimension->key}] cannot be combined with a relative date preset on the same dimension.",
                        );

                        break;
                    }
                }
            },
        ];
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function toDatasetQuery(): DatasetQuery
    {
        /**
         * @var array{
         *     dataset: string,
         *     dimensions: list<string>,
         *     measures: list<string>,
         *     filters?: list<array{
         *         dimension: string,
         *         operator: string,
         *         value: mixed
         *     }>,
         *     limit?: int,
         *      relative_date?: array{
         *          dimension: string,
         *          preset: string
         *      }|null,
         * } $validated
         */
        $validated = $this->validated();

        $dataset = DatasetKey::from($validated['dataset']);
        $filterValidator = app(FilterValidator::class);

        $filters = array_map(
            fn (array $filter): FilterCondition => $filterValidator->validate(
                $dataset,
                $filter['dimension'],
                $filter['operator'],
                $filter['value'],
            ),
            $validated['filters'] ?? [],
        );

        $reportingTimezone = new ReportingTimezone(
            (string) config('analytics.reporting_timezone'),
        );

        $relativeDate = $validated['relative_date'] ?? null;

        if ($relativeDate !== null) {
            return app(RelativeDateDatasetQueryFactory::class)->create(
                dataset: $dataset,
                dimensions: $validated['dimensions'],
                measures: $validated['measures'],
                filters: $filters,
                relativeDateDimension: $relativeDate['dimension'],
                preset: RelativeDatePreset::from(
                    $relativeDate['preset'],
                ),
                now: CarbonImmutable::now(
                    $reportingTimezone->toDateTimeZone(),
                ),
                reportingTimezone: $reportingTimezone,
                limit: $validated['limit'] ?? 100,
            );
        }

        return new DatasetQuery(
            dataset: $dataset,
            dimensions: $validated['dimensions'],
            measures: $validated['measures'],
            filters: $filters,
            limit: $validated['limit'] ?? 100,
            reportingTimezone: $reportingTimezone,
        );
    }

    /**
     * @return array{
     *     dimensions: list<string>,
     *     measures: list<string>,
     *     filters: list<array{
     *         dimension: string,
     *         operator: string,
     *         value: mixed
     *     }>,
     *     relative_date: array{
     *         dimension: string,
     *         preset: string
     *     }|null,
     *     limit: int,
     *     visualization: array{
     *          type: string,
     *          dimension: string,
     *          measure: string
     *      }|null,
     * }
     */
    public function toStoredDefinition(): array
    {
        $validated = $this->validated();
        $dataset = DatasetKey::from($validated['dataset']);
        $filterValidator = app(FilterValidator::class);

        $filters = array_map(
            function (array $filter) use (
                $dataset, $filterValidator
            ): array {
                $condition = $filterValidator->validate(
                    $dataset,
                    $filter['dimension'],
                    $filter['operator'],
                    $filter['value'],
                );

                return [
                    'dimension' => $condition->dimension,
                    'operator' => $condition->operator->value,
                    'value' => $condition->value,
                ];
            },
            $validated['filters'] ?? [],
        );

        $visualization = $validated['visualization'] ?? null;

        return [
            'dimensions' => $validated['dimensions'],
            'measures' => $validated['measures'],
            'filters' => $filters,
            'relative_date' => $validated['relative_date'] ?? null,
            'limit' => $validated['limit'] ?? 100,
            'visualization' => $visualization === null
                ? null
                : [
                    'type' => $visualization['type'],
                    'dimension' => $visualization['dimension'],
                    'measure' => $visualization['measure'],
                    'series' => $visualization['series'] ?? null,
                ],
        ];
    }
}
