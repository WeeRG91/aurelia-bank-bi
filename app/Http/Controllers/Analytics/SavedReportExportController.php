<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Auditing\AuditAction;
use App\Analytics\Auditing\AuditContext;
use App\Analytics\Auditing\AuditOutcome;
use App\Analytics\Auditing\AuditSubjectType;
use App\Analytics\Auditing\WebAuditRecorder;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Exports\ReportExportGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\ExportSavedReportRequest;
use App\Models\Employee;
use App\Models\SavedReport;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateInvalidTimeZoneException;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use LogicException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Throwable;

final class SavedReportExportController extends Controller
{
    /**
     * @throws DateInvalidTimeZoneException
     * @throws Throwable
     */
    public function __invoke(
        ExportSavedReportRequest $request,
        SavedReport $savedReport,
        ReportExportGenerator $generator,
        WebAuditRecorder $audit,
    ): Response {
        /** @var User $user */
        $user = $request->user();

        $dataset = $savedReport->dataset;
        $definition = $savedReport->definition;

        /** @var Employee $employee */
        $employee = $user->employee;

        if (! $employee instanceof Employee) {
            throw new LogicException(
                'The authenticated user has no employee profile.',
            );
        }

        if (
            ! $dataset instanceof DatasetKey
            || ! is_array($definition)
        ) {
            throw new LogicException(
                'The saved report definition is invalid.',
            );
        }

        $format = $request->exportFormat();
        $startedAt = microtime(true);

        $file = $generator->generate(
            user: $user,
            dataset: $dataset,
            definition: $definition,
            format: $format,
        );

        $audit->record(
            request: $request,
            action: AuditAction::EXPORT_DOWNLOADED,
            outcome: AuditOutcome::SUCCEEDED,
            actor: $employee,
            dataset: $dataset,
            subjectType: AuditSubjectType::SAVED_REPORT,
            subjectId: $savedReport->getKey(),
            context: AuditContext::from([
                'definition_version' => $savedReport
                    ->definition_version,
                'duration_ms' => max(
                    0,
                    (int) round(
                        (microtime(true) - $startedAt) * 1_000,
                    ),
                ),
                'file_size_bytes' => strlen($file->contents),
                'format' => $format->value,
                'row_count' => $file->rowCount,
            ]),
        );

        $now = CarbonImmutable::now(
            (string) config('analytics.reporting_timezone'),
        );

        $baseName = Str::slug($savedReport->name);

        if ($baseName === '') {
            $baseName = 'saved-report';
        }

        $filename = sprintf(
            '%s-%s.%s',
            $baseName,
            $now->format('Ymd-His'),
            $format->extension(),
        );

        return response($file->contents, 200, [
            'Content-Type' => $format->contentType(),
            'Content-Disposition' => HeaderUtils::makeDisposition(
                'attachment',
                $filename,
            ),
            'Content-Length' => (string) strlen($file->contents),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
