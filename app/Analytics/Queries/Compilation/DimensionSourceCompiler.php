<?php

namespace App\Analytics\Queries\Compilation;

use App\Analytics\Queries\Sources\DimensionSource;
use App\Analytics\Queries\Sources\DimensionSourceKind;
use App\Analytics\Time\ReportingTimezone;

final class DimensionSourceCompiler
{
    public function compile(
        DimensionSource $source,
        ReportingTimezone $reportingTimezone,
    ): string {
        if ($source->kind === DimensionSourceKind::COLUMN) {
            return $source->column;
        }

        $localTimestamp = sprintf(
            "timezone('%s', %s)",
            $this->escapeLiteral($reportingTimezone->name),
            $source->column,
        );

        return match ($source->kind) {
            DimensionSourceKind::COLUMN => $source->column,

            DimensionSourceKind::LOCAL_DATE => sprintf(
                'CAST(%s AS date)',
                $localTimestamp,
            ),

            DimensionSourceKind::LOCAL_MONTH => sprintf(
                "CAST(date_trunc('month', %s) AS date)",
                $localTimestamp,
            ),

            DimensionSourceKind::LOCAL_QUARTER => sprintf(
                "CAST(date_trunc('quarter', %s) AS date)",
                $localTimestamp,
            ),

            DimensionSourceKind::LOCAL_YEAR => sprintf(
                "CAST(date_trunc('year', %s) AS date)",
                $localTimestamp,
            ),
        };
    }

    private function escapeLiteral(string $value): string
    {
        return str_replace("'", "''", $value);
    }
}
