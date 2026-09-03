<?php

namespace App\Analytics\Exports;

use InvalidArgumentException;
use RuntimeException;

final class CsvReportWriter
{
    /**
     * @param  list<ExportColumn>  $columns
     * @param  list<array<string, mixed>|object>  $rows
     */
    public function write(
        array $columns,
        array $rows
    ): string {
        if ($columns === []) {
            throw new InvalidArgumentException(
                'A CSV export requires at least one column.',
            );
        }

        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw new RuntimeException(
                'Unable to create the temporary CSV stream.',
            );
        }

        try {
            fwrite($stream, "\xEF\xBB\xBF");

            $this->writeRow(
                $stream,
                array_map(
                    static fn (ExportColumn $column): string => $column->label,
                    $columns,
                ),
            );

            foreach ($rows as $row) {
                $values = is_object($row)
                    ? get_object_vars($row)
                    : $row;

                $this->writeRow(
                    $stream,
                    array_map(
                        fn (ExportColumn $column): string => $this->cellValue(
                            $values[$column->key] ?? null,
                        ),
                        $columns,
                    ),
                );
            }

            rewind($stream);

            $contents = stream_get_contents($stream);

            if ($contents === false) {
                throw new RuntimeException(
                    'Unable to read the generated CSV.',
                );
            }

            return $contents;
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param  resource  $stream
     * @param  list<string>  $values
     */
    private function writeRow($stream, array $values): void
    {
        $written = fputcsv(
            $stream,
            $values,
            separator: ',',
            enclosure: '"',
            escape: '',
            eol: "\r\n",
        );

        if ($written === false) {
            throw new RuntimeException(
                'Unable to write the CSV stream.',
            );
        }
    }

    private function cellValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException(
                'CSV cells must contain scalar or null values.',
            );
        }

        $trimmed = ltrim($value);

        if (
            $trimmed !== ''
            && str_contains('=+-@', $trimmed[0])
            && ! is_numeric($trimmed)
        ) {
            return "'{$value}";
        }

        return $value;
    }
}
