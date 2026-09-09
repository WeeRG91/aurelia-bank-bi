<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Throwable;

final class SavedReportDuplicateController extends Controller
{
    public function __invoke(
        Request $request,
        SavedReport $savedReport,
        WebAuditRecorder $audit,
    ): RedirectResponse {
        Gate::authorize('duplicate', $savedReport);
        Gate::authorize('create', SavedReport::class);

        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        try {
            /** @var SavedReport $copy */
            $copy = DB::transaction(
                function () use (
                    $request,
                    $savedReport,
                    $audit,
                    $employee,
                ): SavedReport {
                    /** @var SavedReport $copy */
                    $copy = $employee->savedReports()->create([
                        'name' => Str::limit(
                            $savedReport->name,
                            143,
                            '',
                        ).' (Copy)',
                        'description' => $savedReport->description,
                        'dataset' => $savedReport->dataset,
                        'definition_version' => $savedReport
                            ->definition_version,
                        'definition' => $savedReport->definition,
                    ]);

                    $definition = $copy->definition;

                    $audit->record(
                        request: $request,
                        action: AuditAction::REPORT_DUPLICATED,
                        outcome: AuditOutcome::SUCCEEDED,
                        actor: $employee,
                        dataset: $copy->dataset,
                        subjectType: AuditSubjectType::SAVED_REPORT,
                        subjectId: $copy->getKey(),
                        context: AuditContext::from([
                            'definition_version' => $copy
                                ->definition_version,
                            'dimension_count' => count(
                                $definition['dimensions'] ?? [],
                            ),
                            'filter_count' => count(
                                $definition['filters'] ?? [],
                            ),
                            'limit' => (int) (
                                $definition['limit'] ?? 100
                            ),
                            'measure_count' => count(
                                $definition['measures'] ?? [],
                            ),
                        ]),
                    );

                    return $copy;
                },
            );
        } catch (Throwable $exception) {
            report($exception);

            return to_route('analytics.saved-reports.index')
                ->with(
                    'error',
                    'The report could not be duplicated. Please try again.',
                );
        }

        return to_route(
            'analytics.report-builder',
            ['savedReport' => $copy],
        )->with(
            'status',
            "“{$copy->name}” was duplicated successfully.",
        );
    }
}
