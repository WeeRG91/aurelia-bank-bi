<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Datasets\DatasetAccess;
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

        return view('analytics.datasets.show', [
            'dataset' => $definition,
        ]);
    }
}
