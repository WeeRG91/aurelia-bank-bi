<?php

namespace App\Http\Controllers\Analytics;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Exports\CsvReportWriter;
use App\Analytics\Exports\ExportFormat;
use App\Analytics\Exports\ExportSchemaFactory;
use App\Analytics\Exports\XlsxReportWriter;
use App\Analytics\Queries\AuthorizedDatasetQueryExecutor;
use App\Analytics\Queries\ReportDefinitionQueryFactory;
use App\Analytics\Queries\Sources\DatasetSourceRegistry;
use App\Analytics\Time\ReportingTimezone;
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

final class SavedReportExportController extends Controller
{
    /**
     * @throws DateInvalidTimeZoneException
     */
    public function __invoke(
        ExportSavedReportRequest $request,
        SavedReport $savedReport,
        ReportDefinitionQueryFactory $queryFactory,
        DatasetSourceRegistry $sources,
        AuthorizedDatasetQueryExecutor $executor,
        ExportSchemaFactory $schemaFactory,
        CsvReportWriter $csvWriter,
        XlsxReportWriter $xlsxWriter,
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

        $reportingTimezone = new ReportingTimezone(
            (string) config('analytics.reporting_timezone'),
        );

        $now = CarbonImmutable::now(
            $reportingTimezone->toDateTimeZone(),
        );

        $query = $queryFactory->create(
            dataset: $dataset,
            definition: $definition,
            now: $now,
            reportingTimezone: $reportingTimezone,
        );

        $rows = $executor->executeFor(
            $user,
            $sources->get($query->dataset),
            $query,
        );

        $columns = $schemaFactory->forQuery($query);
        $format = $request->exportFormat();

        $contents = match ($format) {
            ExportFormat::CSV => $csvWriter->write(
                $columns,
                $rows,
            ),
            ExportFormat::XLSX => $xlsxWriter->write(
                $columns,
                $rows,
            ),
        };

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

        return response($contents, 200, [
            'Content-Type' => $format->contentType(),
            'Content-Disposition' => HeaderUtils::makeDisposition(
                'attachment',
                $filename,
            ),
            'Content-Length' => (string) strlen($contents),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
