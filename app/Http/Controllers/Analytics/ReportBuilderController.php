<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetDefinition;
use App\Analytics\Time\ReportingTimezone;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use JsonException;

class ReportBuilderController extends Controller
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
            ],
            $datasetAccess->discoverableTo($user),
        );

        $bootstrap = [
            'reportingTimezone' => $reportingTimezone->name,
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
