<?php

namespace Tests\Unit;

use App\Analytics\Time\ReportingTimezone;
use App\Analytics\Warehouse\Aggregates\DailyTransactionAggregateQuery;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DailyTransactionAggregateQueryTest extends TestCase
{
    public function test_query_aggregates_booked_facts_in_reporting_timezone(): void
    {
        $compiled = (new DailyTransactionAggregateQuery)->compile(
            new ReportingTimezone('Europe/Luxembourg'),
            new DateTimeImmutable(
                '2026-10-12T17:30:00+02:00',
            ),
        );

        $this->assertStringContainsString(
            'timezone(',
            $compiled->sql,
        );

        $this->assertStringContainsString(
            'fact_transactions.booked_at IS NOT NULL',
            $compiled->sql,
        );

        $this->assertStringContainsString(
            'COUNT(*)::bigint AS transaction_count',
            $compiled->sql,
        );

        $this->assertStringContainsString(
            'SUM(activity.signed_amount)',
            $compiled->sql,
        );

        $this->assertStringContainsString(
            'GROUP BY',
            $compiled->sql,
        );

        $this->assertStringNotContainsString(
            'Europe/Luxembourg',
            $compiled->sql,
        );

        $this->assertSame(
            [
                '2026-10-12 15:30:00+00:00',
                'Europe/Luxembourg',
                'Europe/Luxembourg',
            ],
            $compiled->bindings,
        );
    }

    public function test_query_preserves_the_complete_aggregate_grain(): void
    {
        $sql = (new DailyTransactionAggregateQuery)
            ->compile(
                new ReportingTimezone('UTC'),
                new DateTimeImmutable(
                    '2026-10-12T15:30:00Z',
                ),
            )
            ->sql;

        foreach ([
            'activity.reporting_timezone',
            'activity.reporting_date_key',
            'activity.branch_key',
            'activity.currency',
            'activity.transaction_type',
        ] as $grainColumn) {
            $this->assertStringContainsString(
                $grainColumn,
                $sql,
            );
        }
    }
}
