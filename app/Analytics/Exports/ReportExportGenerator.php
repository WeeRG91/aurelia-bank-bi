<?php

namespace App\Analytics\Exports;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Queries\AuthorizedDatasetQueryExecutor;
use App\Analytics\Queries\ReportDefinitionQueryFactory;
use App\Analytics\Queries\Sources\DatasetSourceRegistry;
use App\Analytics\Time\ReportingTimezone;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateInvalidTimeZoneException;
use Throwable;

final readonly class ReportExportGenerator
{
    public function __construct(
        private ReportDefinitionQueryFactory $queryFactory,
        private DatasetSourceRegistry $sources,
        private AuthorizedDatasetQueryExecutor $executor,
        private ExportSchemaFactory $schemaFactory,
        private CsvReportWriter $csvWriter,
        private XlsxReportWriter $xlsxWriter,
    ) {}

    /**
     * @throws DateInvalidTimeZoneException
     * @throws Throwable
     */
    public function generate(
        User $user,
        DatasetKey $dataset,
        array $definition,
        ExportFormat $format,
    ): GeneratedReportFile {
        $reportingTimezone = new ReportingTimezone(
            (string) config('analytics.reporting_timezone'),
        );

        $query = $this->queryFactory->create(
            dataset: $dataset,
            definition: $definition,
            now: CarbonImmutable::now(
                $reportingTimezone->toDateTimeZone(),
            ),
            reportingTimezone: $reportingTimezone,
        );

        $rows = $this->executor->executeFor(
            $user,
            $this->sources->get($query->dataset),
            $query,
        );

        $columns = $this->schemaFactory->forQuery($query);

        $contents = match ($format) {
            ExportFormat::CSV => $this->csvWriter->write(
                $columns,
                $rows,
            ),
            ExportFormat::XLSX => $this->xlsxWriter->write(
                $columns,
                $rows,
            ),
        };

        return new GeneratedReportFile(
            contents: $contents,
            rowCount: count($rows),
        );
    }
}
