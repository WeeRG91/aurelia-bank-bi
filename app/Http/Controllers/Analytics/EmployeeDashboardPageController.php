<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use JsonException;
use LogicException;

final class EmployeeDashboardPageController extends Controller
{
    /**
     * @throws JsonException
     */
    public function __invoke(Request $request): View
    {
        Gate::authorize('viewAny', Dashboard::class);

        /** @var User $user */
        $user = $request->user();

        $employee = $user->employee;

        if (! $employee instanceof Employee) {
            throw new LogicException(
                'The authenticated user has no employee profile.',
            );
        }

        $savedReports = $employee
            ->savedReports()
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->filter(
                static fn (
                    SavedReport $savedReport,
                ): bool => Gate::forUser($user)
                    ->allows('export', $savedReport),
            )
            ->map(
                static fn (
                    SavedReport $savedReport,
                ): array => [
                    'id' => (int) $savedReport->getKey(),
                    'name' => $savedReport->name,
                    'dataset' => $savedReport->dataset->value,
                ],
            )
            ->values()
            ->all();

        $bootstrap = [
            'indexUrl' => route(
                'analytics.dashboards.index',
            ),
            'storeUrl' => route(
                'analytics.dashboards.store',
            ),
            'savedReports' => $savedReports,
        ];

        return view('analytics.dashboards.index', [
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
