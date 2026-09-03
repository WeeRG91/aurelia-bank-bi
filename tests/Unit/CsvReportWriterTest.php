<?php

namespace Tests\Unit;

use App\Analytics\Datasets\FieldDataType;
use App\Analytics\Exports\CsvReportWriter;
use App\Analytics\Exports\ExportColumn;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

class CsvReportWriterTest extends TestCase
{
    public function test_it_writes_utf8_csv_from_database_rows(): void
    {
        $arrayRow = [
            'transaction_reference' => 'TXN-0001',
            'merchant_name' => 'Aurelia Café',
            'amount' => '1250.50',
        ];

        $objectRow = new stdClass;
        $objectRow->transaction_reference = 'TXN-0002';
        $objectRow->merchant_name = 'Market, Central';
        $objectRow->amount = '-42.75';

        $columns = [
            new ExportColumn(
                'transaction_reference',
                'Transaction reference',
                FieldDataType::STRING,
            ),
            new ExportColumn(
                'merchant_name',
                'Merchant name',
                FieldDataType::STRING,
            ),
            new ExportColumn(
                'amount',
                'Amount',
                FieldDataType::DECIMAL,
            ),
        ];

        $csv = (new CsvReportWriter)->write(
            $columns,
            [$arrayRow, $objectRow],
        );

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString(
            "\"Transaction reference\",\"Merchant name\",Amount\r\n",
            $csv,
        );
        $this->assertStringContainsString(
            "TXN-0001,\"Aurelia Café\",1250.50\r\n",
            $csv,
        );
        $this->assertStringContainsString(
            "TXN-0002,\"Market, Central\",-42.75\r\n",
            $csv,
        );
    }

    public function test_it_neutralizes_formula_like_text(): void
    {
        $columns = [
            new ExportColumn(
                'description',
                'Description',
                FieldDataType::STRING,
            ),
            new ExportColumn(
                'amount',
                'Amount',
                FieldDataType::DECIMAL,
            ),
        ];

        $csv = (new CsvReportWriter)->write(
            $columns,
            [
                [
                    'description' => '=HYPERLINK("https://example.test")',
                    'amount' => '-125.50',
                ],
            ],
        );

        $this->assertStringContainsString(
            "'=HYPERLINK(\"\"https://example.test\"\")",
            $csv,
        );

        $this->assertStringContainsString(
            ',-125.50',
            $csv,
        );
    }

    public function test_it_rejects_an_empty_column_selection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A CSV export requires at least one column.',
        );

        (new CsvReportWriter)->write([], []);
    }

    public function test_export_columns_require_safe_keys_and_labels(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid export column key [transactions.amount].',
        );

        new ExportColumn(
            'transactions.amount',
            'Amount',
            FieldDataType::DECIMAL,
        );
    }
}
