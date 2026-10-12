<?php

namespace App\Analytics\Warehouse\Aggregates;

use App\Analytics\Queries\CompiledQuery;
use App\Analytics\Time\ReportingTimezone;
use DateTimeImmutable;
use DateTimeZone;

final readonly class DailyTransactionAggregateQuery
{
    public function compile(
        ReportingTimezone $reportingTimezone,
        DateTimeImmutable $refreshedAt,
    ): CompiledQuery {
        $refreshedAtUtc = $refreshedAt
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:sP');

        return new CompiledQuery(
            sql: <<<'SQL'
                SELECT
                    activity.reporting_timezone,
                    activity.reporting_date_key,
                    activity.branch_key,
                    activity.currency,
                    activity.transaction_type,
                    COUNT(*)::bigint AS transaction_count,
                    CAST(
                        SUM(activity.absolute_amount)
                        AS numeric(24, 2)
                    ) AS total_amount,
                    CAST(
                        SUM(
                            CASE activity.direction
                                WHEN 'incoming'
                                    THEN activity.absolute_amount
                                ELSE 0
                            END
                        )
                        AS numeric(24, 2)
                    ) AS incoming_amount,
                    CAST(
                        SUM(
                            CASE activity.direction
                                WHEN 'outgoing'
                                    THEN activity.absolute_amount
                                ELSE 0
                            END
                        )
                        AS numeric(24, 2)
                    ) AS outgoing_amount,
                    CAST(
                        SUM(activity.signed_amount)
                        AS numeric(24, 2)
                    ) AS net_cash_flow,
                    CAST(? AS timestamp with time zone) AS refreshed_at
                FROM (
                    SELECT
                        CAST(? AS varchar(64))
                            AS reporting_timezone,
                        CAST(
                            to_char(
                                timezone(
                                    ?,
                                    fact_transactions.booked_at
                                ),
                                'YYYYMMDD'
                            )
                            AS integer
                        ) AS reporting_date_key,
                        fact_transactions.branch_key,
                        fact_transactions.currency,
                        fact_transactions.transaction_type,
                        fact_transactions.direction,
                        fact_transactions.absolute_amount,
                        fact_transactions.signed_amount
                    FROM fact_transactions
                    WHERE fact_transactions.booked_at IS NOT NULL
                ) AS activity
                GROUP BY
                    activity.reporting_timezone,
                    activity.reporting_date_key,
                    activity.branch_key,
                    activity.currency,
                    activity.transaction_type
                SQL,
            bindings: [
                $refreshedAtUtc,
                $reportingTimezone->name,
                $reportingTimezone->name,
            ],
        );
    }
}
