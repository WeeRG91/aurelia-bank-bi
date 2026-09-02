<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetDefinition;
use App\Analytics\Datasets\DimensionDefinition;
use App\Analytics\Datasets\MeasureDefinition;
use App\Analytics\Time\ReportingTimezone;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use JsonException;

final class ReportBuilderController extends Controller
{
    /**
     * @throws JsonException
     */
    public function __invoke(
        Request $request,
        DatasetAccess $datasetAccess,
    ): View {
        /** @var User $user */
        $user = $request->user();

        $reportingTimezone = new ReportingTimezone(
            (string) config('analytics.reporting_timezone'),
        );

        $datasets = array_map(
            static fn (DatasetDefinition $dataset): array => [
                'key' => $dataset->key->value,
                'label' => $dataset->label,
                'description' => $dataset->description,
                'grain' => $dataset->grain,
                'dimensions' => array_map(
                    static fn (DimensionDefinition $dimension): array => [
                        'key' => $dimension->key,
                        'label' => $dimension->label,
                        'description' => $dimension->description,
                        'dataType' => $dimension->dataType->value,
                        'kind' => $dimension->kind->value,
                        'sensitivity' => $dimension->sensitivity->value,
                        'nullable' => $dimension->nullable,
                    ],
                    $dataset->dimensions(),
                ),
                'measures' => array_map(
                    static fn (MeasureDefinition $measure): array => [
                        'key' => $measure->key,
                        'label' => $measure->label,
                        'description' => $measure->description,
                        'dataType' => $measure->dataType->value,
                        'aggregation' => $measure->aggregation->value,
                        'sensitivity' => $measure->sensitivity->value,
                        'currencyDimension' => $measure->currencyDimension,
                        'requiredDimensions' => $measure->requiredContextDimensions(),
                    ],
                    $dataset->measures(),
                ),
            ],
            $datasetAccess->discoverableTo($user),
        );

        $bootstrap = [
            'reportingTimezone' => $reportingTimezone->name,
            'previewUrl' => route('analytics.report-preview'),
            'datasets' => $datasets,
        ];

        return view('analytics.report-builder', [
            'bootstrapJson' => json_encode(
                $bootstrap,
                JSON_THROW_ON_ERROR
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT,
            ),
        ]);
    }
}
