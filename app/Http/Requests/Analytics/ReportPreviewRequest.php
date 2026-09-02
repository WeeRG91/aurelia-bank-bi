<?php

namespace App\Http\Requests\Analytics;

use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Filters\FilterCondition;
use App\Analytics\Filters\FilterOperator;
use App\Analytics\Filters\FilterValidator;
use App\Analytics\Filters\InvalidFilter;
use App\Analytics\Queries\DatasetQuery;
use App\Analytics\Time\ReportingTimezone;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class ReportPreviewRequest extends FormRequest
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
            },
        ];
    }

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
         *     limit?: int
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

        return new DatasetQuery(
            dataset: $dataset,
            dimensions: $validated['dimensions'],
            measures: $validated['measures'],
            filters: $filters,
            limit: $validated['limit'] ?? 100,
            reportingTimezone: new ReportingTimezone(
                (string) config('analytics.reporting_timezone'),
            ),
        );
    }
}
