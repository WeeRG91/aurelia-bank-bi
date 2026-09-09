<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportExportController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ReportExport::class);

        /** @var User $user */
        $user = $request->user();

        $employee = $user->employee;

        $exports = $employee
            ->requestedReportExports()
            ->with('savedReport')
            ->latest()
            ->paginate(20);

        return view('analytics.report_exports.index', [
            'exports' => $exports,
        ]);
    }

    public function download(
        Request $request,
        ReportExport $reportExport,
        WebAuditRecorder $audit,
    ): StreamedResponse {
        Gate::authorize('download', $reportExport);

        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        if (! $employee instanceof Employee) {
            throw new LogicException(
                'The authenticated user has no employee profile.',
            );
        }

        $disk = $reportExport->disk;
        $path = $reportExport->path;
        $filename = $reportExport->filename;

        abort_unless(
            is_string($disk)
            && is_string($path)
            && is_string($filename)
            && Storage::disk($disk)->exists($path),
            404,
            'Export file not found',
        );

        $audit->record(
            request: $request,
            action: AuditAction::EXPORT_DOWNLOADED,
            outcome: AuditOutcome::SUCCEEDED,
            actor: $employee,
            dataset: $reportExport->dataset,
            subjectType: AuditSubjectType::REPORT_EXPORT,
            subjectId: $reportExport->getKey(),
            context: AuditContext::from([
                'definition_version' => $reportExport
                    ->definition_version,
                'file_size_bytes' => $reportExport
                    ->file_size_bytes,
                'format' => $reportExport->format->value,
                'row_count' => $reportExport->row_count,
                'status_from' => $reportExport->status->value,
            ]),
        );

        return Storage::disk($disk)->download(
            $path,
            $filename,
            [
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
