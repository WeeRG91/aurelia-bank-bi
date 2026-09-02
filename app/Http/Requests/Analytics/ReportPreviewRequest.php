<?php

namespace App\Http\Requests\Analytics;

use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
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
         *     limit?: int
         * } $validated
         */
        $validated = $this->validated();

        return new DatasetQuery(
            dataset: DatasetKey::from($validated['dataset']),
            dimensions: $validated['dimensions'],
            measures: $validated['measures'],
            limit: $validated['limit'] ?? 100,
            reportingTimezone: new ReportingTimezone(
                (string) config('analytics.reporting_timezone'),
            ),
        );
    }
}
