<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Analytics\Datasets\DatasetAccess;
use App\Analytics\Datasets\DatasetFieldAccess;
use App\Analytics\Datasets\DatasetRegistry;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;

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
        WebAuditRecorder $audit,
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

        /** @var Employee $employee */
        $employee = $user->employee;

        if (! $employee instanceof Employee) {
            throw new LogicException(
                'The authenticated user has no employee profile.',
            );
        }

        $audit->record(
            request: $request,
            action: AuditAction::DATASET_INSPECTED,
            outcome: AuditOutcome::SUCCEEDED,
            actor: $employee,
            dataset: $definition->key,
            subjectType: AuditSubjectType::DATASET,
            subjectId: $definition->key->value,
            context: AuditContext::from([
                'dimension_count' => count($dimensions),
                'measure_count' => count($measures),
            ]),
        );

        return view('analytics.datasets.show', [
            'dataset' => $definition,
            'dimensions' => $dimensions,
            'measures' => $measures,
            'canInspectRegistry' => $canInspectRegistry,
        ]);
    }
}
