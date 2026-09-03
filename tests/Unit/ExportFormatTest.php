<?php

namespace Tests\Unit;

use App\Analytics\Exports\ExportFormat;
use PHPUnit\Framework\TestCase;

class ExportFormatTest extends TestCase
{
    public function test_export_formats_have_stable_values(): void
    {
        $this->assertSame(
            ['csv', 'xlsx'],
            array_column(ExportFormat::cases(), 'value'),
        );
    }

    public function test_formats_define_download_metadata(): void
    {
        $this->assertSame('csv', ExportFormat::CSV->extension());
        $this->assertSame(
            'text/csv; charset=UTF-8',
            ExportFormat::CSV->contentType(),
        );

        $this->assertSame('xlsx', ExportFormat::XLSX->extension());
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ExportFormat::XLSX->contentType(),
        );
    }
}
