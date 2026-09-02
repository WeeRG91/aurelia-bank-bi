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
     *     limit: int
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

        return [
            'dimensions' => $validated['dimensions'],
            'measures' => $validated['measures'],
            'filters' => $filters,
            'relative_date' => $validated['relative_date'] ?? null,
            'limit' => $validated['limit'] ?? 100,
        ];
    }
}
