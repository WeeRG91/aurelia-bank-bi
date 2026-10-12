<?php

namespace App\Analytics\Warehouse\Aggregates;

use App\Analytics\Time\ReportingTimezone;
use DateTimeImmutable;

interface DailyTransactionAggregateRefresher
{
    public function refresh(
        ReportingTimezone $reportingTimezone,
        DateTimeImmutable $refreshedAt,
    ): DailyTransactionAggregateRefreshResult;
}
