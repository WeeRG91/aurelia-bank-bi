<?php

namespace App\Analytics\Exports;

use App\Analytics\Datasets\FieldDataType;
use DateTimeImmutable;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Throwable;

final class XlsxReportWriter
{
    /**
     * @param  list<ExportColumn>  $columns
     * @param  list<array<string, mixed>|object>  $rows
     */
    public function write(array $columns, array $rows): string
    {
        if ($columns === []) {
            throw new InvalidArgumentException(
                'An XLSX export requires at least one column.',
            );
        }

        $spreadsheet = new Spreadsheet;
        $temporaryFile = tempnam(
            sys_get_temp_dir(),
            'aurelia-xlsx-',
        );

        if ($temporaryFile === false) {
            $spreadsheet->disconnectWorksheets();

            throw new RuntimeException(
                'Unable to create the temporary XLSX file.',
            );
        }

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Report');

            foreach ($columns as $index => $column) {
                $coordinate = Coordinate::stringFromColumnIndex(
                    $index + 1,
                ).'1';

                $sheet->setCellValueExplicit(
                    $coordinate,
                    $column->label,
                    DataType::TYPE_STRING
                );

                $sheet->getColumnDimension(
                    Coordinate::stringFromColumnIndex($index + 1),
                )->setAutoSize(true);
            }

            foreach ($rows as $rowIndex => $row) {
                $values = is_object($row)
                    ? get_object_vars($row)
                    : $row;

                foreach ($columns as $columnIndex => $column) {
                    $coordinate = Coordinate::stringFromColumnIndex(
                        $columnIndex + 1,
                    ).($rowIndex + 2);

                    $this->writeCell(
                        $sheet,
                        $coordinate,
                        $values[$column->key] ?? null,
                        $column->dataType,
                    );
                }
            }

            $lastColumn = Coordinate::stringFromColumnIndex(
                count($columns),
            );

            $sheet
                ->getStyle("A1:{$lastColumn}1")
                ->getFont()
                ->setBold(true);

            $sheet->freezePane('A2');
            $sheet->setAutoFilter("A1:{$lastColumn}1");

            (new Xlsx($spreadsheet))->save($temporaryFile);

            $contents = file_get_contents($temporaryFile);

            if ($contents === false) {
                throw new RuntimeException(
                    'Unable to read the generated XLSX file.',
                );
            }

            return $contents;
        } finally {
            $spreadsheet->disconnectWorksheets();

            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    /**
     * @throws Throwable
     */
    private function writeCell(
        mixed $sheet,
        string $coordinate,
        mixed $value,
        FieldDataType $dataType,
    ): void {
        if ($value === null) {
            $sheet->setCellValue($coordinate, null);

            return;
        }

        match ($dataType) {
            FieldDataType::INTEGER,
            FieldDataType::DECIMAL => $this->writeNumericCell(
                $sheet,
                $coordinate,
                $value,
            ),
            FieldDataType::BOOLEAN => $sheet->setCellValueExplicit(
                $coordinate,
                filter_var($value, FILTER_VALIDATE_BOOL),
                DataType::TYPE_BOOL,
            ),
            FieldDataType::DATE => $this->writeDateCell(
                $sheet,
                $coordinate,
                $value,
                'yyyy-mm-dd',
            ),
            FieldDataType::DATETIME => $this->writeDateCell(
                $sheet,
                $coordinate,
                $value,
                'yyyy-mm-dd hh:mm:ss',
            ),
            FieldDataType::STRING => $sheet->setCellValueExplicit(
                $coordinate,
                (string) $value,
                DataType::TYPE_STRING,
            ),
        };
    }

    private function writeNumericCell(
        Worksheet $sheet,
        string $coordinate,
        mixed $value,
    ): void {
        if (! is_string($value) && ! is_int($value)) {
            throw new InvalidArgumentException(
                "XLSX numeric cell [{$coordinate}] requires an integer or decimal string.",
            );
        }

        $number = (string) $value;

        if (preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/D', $number) !== 1) {
            throw new InvalidArgumentException(
                "Invalid numeric value for XLSX cell [{$coordinate}].",
            );
        }

        // Conservatively count all digits, including trailing decimal zeros.
        $digits = str_replace(['-', '.'], '', $number);

        if (strlen($digits) > 15) {
            $sheet->setCellValueExplicit(
                $coordinate,
                $number,
                DataType::TYPE_STRING,
            );

            $sheet->getStyle($coordinate)
                ->getNumberFormat()
                ->setFormatCode('@');

            $sheet->getComment($coordinate)
                ->getText()
                ->createTextRun(
                    'Stored as text to preserve precision beyond Excel numeric limits.',
                );

            return;
        }

        $sheet->setCellValueExplicit(
            $coordinate,
            $number,
            DataType::TYPE_NUMERIC,
        );
    }

    /**
     * @throws Throwable
     */
    private function writeDateCell(
        mixed $sheet,
        string $coordinate,
        mixed $value,
        string $format,
    ): void {
        if (! is_string($value)) {
            throw new InvalidArgumentException(
                "Invalid date value provided for XLSX cell [{$coordinate}].",
            );
        }

        $sheet->setCellValue(
            $coordinate,
            Date::PHPToExcel(new DateTimeImmutable($value)),
        );

        $sheet
            ->getStyle($coordinate)
            ->getNumberFormat()
            ->setFormatCode($format);
    }
}
