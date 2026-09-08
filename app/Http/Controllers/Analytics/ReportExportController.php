<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
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
        ReportExport $reportExport,
    ): StreamedResponse {
        Gate::authorize('download', $reportExport);

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
