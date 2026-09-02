<?php

namespace Tests\Unit;

use App\Analytics\Queries\Compilation\DimensionSourceCompiler;
use App\Analytics\Queries\Sources\DimensionSource;
use App\Analytics\Queries\Sources\DimensionSourceKind;
use App\Analytics\Time\ReportingTimezone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DimensionSourceCompilerTest extends TestCase
{
    public function test_plain_column_is_not_transformed(): void
    {
        $sql = (new DimensionSourceCompiler)->compile(
            new DimensionSource(
                column: 'transactions.currency',
            ),
            new ReportingTimezone('Europe/Luxembourg'),
        );

        $this->assertSame(
            'transactions.currency',
            $sql,
        );
    }

    #[DataProvider('localPeriodExpressions')]
    public function test_local_period_uses_reporting_timezone(
        DimensionSourceKind $kind,
        string $expectedSql,
    ): void {
        $sql = (new DimensionSourceCompiler)->compile(
            new DimensionSource(
                column: 'transactions.booked_at',
                kind: $kind,
            ),
            new ReportingTimezone('Europe/Luxembourg'),
        );

        $this->assertSame($expectedSql, $sql);
    }

    /**
     * @return iterable<string, array{DimensionSourceKind, string}>
     */
    public static function localPeriodExpressions(): iterable
    {
        yield 'local date' => [
            DimensionSourceKind::LOCAL_DATE,
            "CAST(timezone('Europe/Luxembourg', transactions.booked_at) AS date)",
        ];

        yield 'local month' => [
            DimensionSourceKind::LOCAL_MONTH,
            "CAST(date_trunc('month', timezone('Europe/Luxembourg', transactions.booked_at)) AS date)",
        ];

        yield 'local quarter' => [
            DimensionSourceKind::LOCAL_QUARTER,
            "CAST(date_trunc('quarter', timezone('Europe/Luxembourg', transactions.booked_at)) AS date)",
        ];

        yield 'local year' => [
            DimensionSourceKind::LOCAL_YEAR,
            "CAST(date_trunc('year', timezone('Europe/Luxembourg', transactions.booked_at)) AS date)",
        ];
    }

    public function test_utc_can_be_used_as_the_reporting_timezone(): void
    {
        $sql = (new DimensionSourceCompiler)->compile(
            new DimensionSource(
                column: 'transactions.booked_at',
                kind: DimensionSourceKind::LOCAL_MONTH,
            ),
            new ReportingTimezone('UTC'),
        );

        $this->assertSame(
            "CAST(date_trunc('month', timezone('UTC', transactions.booked_at)) AS date)",
            $sql,
        );
    }
}
