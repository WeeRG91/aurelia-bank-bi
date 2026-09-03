<?php

namespace Tests\Unit;

use App\Analytics\Datasets\FieldDataType;
use App\Analytics\Exports\ExportColumn;
use App\Analytics\Exports\XlsxReportWriter;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class XlsxReportWriterTest extends TestCase
{
    public function test_it_generates_a_typed_spreadsheet(): void
    {
        $contents = (new XlsxReportWriter)->write(
            [
                new ExportColumn(
                    'description',
                    'Description',
                    FieldDataType::STRING,
                ),
                new ExportColumn(
                    'snapshot_date',
                    'Snapshot date',
                    FieldDataType::DATE,
                ),
                new ExportColumn(
                    'balance',
                    'Balance',
                    FieldDataType::DECIMAL,
                ),
                new ExportColumn(
                    'account_count',
                    'Account count',
                    FieldDataType::INTEGER,
                ),
            ],
            [
                [
                    'description' => '=2+2',
                    'snapshot_date' => '2026-09-03',
                    'balance' => '1250.50',
                    'account_count' => 3,
                ],
                [
                    'description' => 'High-precision balance',
                    'snapshot_date' => '2026-09-03',
                    'balance' => '12345678901234567.89',
                    'account_count' => 1,
                ],
            ],
        );

        $this->assertStringStartsWith('PK', $contents);

        $temporaryFile = tempnam(
            sys_get_temp_dir(),
            'aurelia-xlsx-test-',
        );

        if ($temporaryFile === false) {
            throw new RuntimeException(
                'Unable to create the test XLSX file.',
            );
        }

        try {
            file_put_contents($temporaryFile, $contents);

            $spreadsheet = IOFactory::load($temporaryFile);
            $sheet = $spreadsheet->getActiveSheet();

            $this->assertSame('Report', $sheet->getTitle());
            $this->assertSame('Description', $sheet->getCell('A1')->getValue());
            $this->assertSame('Snapshot date', $sheet->getCell('B1')->getValue());
            $this->assertSame('Balance', $sheet->getCell('C1')->getValue());

            $this->assertSame('=2+2', $sheet->getCell('A2')->getValue());
            $this->assertSame(
                DataType::TYPE_STRING,
                $sheet->getCell('A2')->getDataType(),
            );

            $this->assertTrue(
                is_numeric($sheet->getCell('B2')->getValue()),
            );
            $this->assertTrue(
                is_numeric($sheet->getCell('C2')->getValue()),
            );
            $this->assertSame(
                DataType::TYPE_NUMERIC,
                $sheet->getCell('C2')->getDataType(),
            );
            $this->assertEquals(
                1250.50,
                $sheet->getCell('C2')->getValue(),
            );

            $this->assertSame(
                DataType::TYPE_STRING,
                $sheet->getCell('C3')->getDataType(),
            );

            $this->assertSame(
                '12345678901234567.89',
                $sheet->getCell('C3')->getValue(),
            );

            $this->assertSame('A2', $sheet->getFreezePane());
            $this->assertSame(
                'A1:D1',
                $sheet->getAutoFilter()->getRange(),
            );

            $spreadsheet->disconnectWorksheets();
        } finally {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    public function test_it_rejects_an_empty_column_selection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'An XLSX export requires at least one column.',
        );

        (new XlsxReportWriter)->write([], []);
    }
}
