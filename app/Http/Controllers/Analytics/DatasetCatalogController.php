<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetFieldAccess;
use App\Analytics\Datasets\DatasetRegistry;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DatasetCatalogController extends Controller
{
    public function index(
        Request $request,
        DatasetAccess $datasetAccess,
    ): View {
        /** @var User $user */
        $user = $request->user();

        return view('analytics.datasets.index', [
            'datasets' => $datasetAccess->catalogFor($user),
        ]);
    }

    public function show(
        Request $request,
        string $dataset,
        DatasetRegistry $datasetRegistry,
        DatasetAccess $datasetAccess,
        DatasetFieldAccess $fieldAccess,
    ): View {
        /** @var User $user */
        $user = $request->user();

        $definition = $datasetRegistry->find($dataset);

        if ($definition === null) {
            abort(404);
        }

        if (! $datasetAccess->canViewDefinition($user, $dataset)) {
            abort(404);
        }

        $canInspectRegistry = $datasetAccess
            ->canInspectRegistry($user);

        $dimensions = $canInspectRegistry
            ? $definition->dimensions()
            : $fieldAccess->dimensionsFor(
                $user,
                $definition->key,
            );

        $measures = $canInspectRegistry
            ? $definition->measures()
            : $fieldAccess->measuresFor(
                $user,
                $definition->key,
            );

        return view('analytics.datasets.show', [
            'dataset' => $definition,
            'dimensions' => $dimensions,
            'measures' => $measures,
            'canInspectRegistry' => $canInspectRegistry,
        ]);
    }
}
