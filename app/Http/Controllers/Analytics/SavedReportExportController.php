<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Exports\ReportExportGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\ExportSavedReportRequest;
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
    ): Response {
        /** @var User $user */
        $user = $request->user();

        $dataset = $savedReport->dataset;
        $definition = $savedReport->definition;

        if (
            ! $dataset instanceof DatasetKey
            || ! is_array($definition)
        ) {
            throw new LogicException(
                'The saved report definition is invalid.',
            );
        }

        $format = $request->exportFormat();

        $file = $generator->generate(
            user: $user,
            dataset: $dataset,
            definition: $definition,
            format: $format,
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
