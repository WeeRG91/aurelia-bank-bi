<?php

namespace App\Analytics\Warehouse\Aggregates;

use App\Analytics\Time\ReportingTimezone;
use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;

final readonly class DatabaseDailyTransactionAggregateRefresher implements DailyTransactionAggregateRefresher
{
    public function __construct(
        private DatabaseManager $database,
        private DailyTransactionAggregateQuery $query,
    ) {}

    public function refresh(
        ReportingTimezone $reportingTimezone,
        DateTimeImmutable $refreshedAt,
    ): DailyTransactionAggregateRefreshResult {
        $connection = $this->database->connection(
            'warehouse',
        );

        return $connection->transaction(
            function (
                ConnectionInterface $connection,
            ) use (
                $reportingTimezone,
                $refreshedAt,
            ): DailyTransactionAggregateRefreshResult {
                $this->acquireRefreshLock(
                    $connection,
                    $reportingTimezone,
                );

                $connection->delete(
                    <<<'SQL'
                    DELETE FROM warehouse.agg_daily_transaction_activity
                    WHERE reporting_timezone = ?
                    SQL,
                    [$reportingTimezone->name],
                );

                $compiled = $this->query->compile(
                    $reportingTimezone,
                    $refreshedAt,
                );

                $insertedRows = $connection->affectingStatement(
                    <<<'SQL'
                    INSERT INTO warehouse.agg_daily_transaction_activity (
                        reporting_timezone,
                        reporting_date_key,
                        branch_key,
                        currency,
                        transaction_type,
                        transaction_count,
                        total_amount,
                        incoming_amount,
                        outgoing_amount,
                        net_cash_flow,
                        refreshed_at
                    )
                    SQL.' '.$compiled->sql,
                    $compiled->bindings,
                );

                return new DailyTransactionAggregateRefreshResult(
                    reportingTimezone: $reportingTimezone,
                    rowCount: $insertedRows,
                    refreshedAt: $refreshedAt,
                );
            },
            1,
        );
    }

    private function acquireRefreshLock(
        ConnectionInterface $connection,
        ReportingTimezone $reportingTimezone,
    ): void {
        $connection->select(
            <<<'SQL'
            SELECT pg_advisory_xact_lock(
                hashtextextended(?, 0)
            )
            SQL,
            [
                'aurelia:agg_daily_transaction_activity:'
                .$reportingTimezone->name,
            ],
        );
    }
}
