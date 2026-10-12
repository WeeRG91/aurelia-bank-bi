<?php

namespace Tests\Unit;

use App\Analytics\Time\ReportingTimezone;
use App\Analytics\Warehouse\Aggregates\DailyTransactionAggregateRefreshResult;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DailyTransactionAggregateRefreshResultTest extends TestCase
{
    public function test_refresh_result_preserves_metadata_in_utc(): void
    {
        $result = new DailyTransactionAggregateRefreshResult(
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
            rowCount: 125,
            refreshedAt: new DateTimeImmutable(
                '2026-10-12T17:30:00+02:00',
            ),
        );

        $this->assertSame(
            'Europe/Luxembourg',
            $result->reportingTimezone->name,
        );

        $this->assertSame(125, $result->rowCount);

        $this->assertSame(
            '2026-10-12T15:30:00+00:00',
            $result->refreshedAt->format(DATE_ATOM),
        );
    }

    public function test_negative_row_count_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Aggregate refresh row count cannot be negative.',
        );

        new DailyTransactionAggregateRefreshResult(
            reportingTimezone: new ReportingTimezone('UTC'),
            rowCount: -1,
            refreshedAt: new DateTimeImmutable(
                '2026-10-12T15:30:00Z',
            ),
        );
    }
}
