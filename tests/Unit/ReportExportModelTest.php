<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Exports\ExportFormat;
use App\Analytics\Exports\ReportExportStatus;
use App\Models\Employee;
use App\Models\ReportExport;
use App\Models\SavedReport;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class ReportExportModelTest extends TestCase
{
    public function test_it_casts_export_state(): void
    {
        $export = (new ReportExport)->forceFill([
            'id' => '01K4M7AT9R6Y0ABCD123456789',
            'dataset' => 'transactions',
            'definition_version' => '1',
            'definition' => [
                'dimensions' => ['currency'],
                'measures' => ['total_amount'],
            ],
            'format' => 'xlsx',
            'status' => 'queued',
            'row_count' => '25',
            'file_size_bytes' => '4096',
            'started_at' => '2026-09-08T10:00:00+00:00',
        ]);

        $this->assertSame(
            DatasetKey::TRANSACTIONS,
            $export->dataset,
        );
        $this->assertSame(ExportFormat::XLSX, $export->format);
        $this->assertSame(
            ReportExportStatus::QUEUED,
            $export->status,
        );
        $this->assertSame(1, $export->definition_version);
        $this->assertSame(25, $export->row_count);
        $this->assertSame(4096, $export->file_size_bytes);

        $this->assertInstanceOf(
            CarbonImmutable::class,
            $export->started_at,
        );

        $this->assertFalse($export->getIncrementing());
        $this->assertSame('string', $export->getKeyType());
    }

    public function test_it_defines_export_ownership_relationships(): void
    {
        $export = new ReportExport;

        $this->assertInstanceOf(
            BelongsTo::class,
            $export->savedReport(),
        );

        $this->assertSame(
            SavedReport::class,
            $export->savedReport()->getRelated()::class,
        );

        $this->assertInstanceOf(
            BelongsTo::class,
            $export->requestedBy(),
        );

        $this->assertSame(
            Employee::class,
            $export->requestedBy()->getRelated()::class,
        );
    }
}
