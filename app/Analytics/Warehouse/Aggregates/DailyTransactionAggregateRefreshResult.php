<?php

namespace App\Analytics\Warehouse\Aggregates;

use App\Analytics\Time\ReportingTimezone;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class DailyTransactionAggregateRefreshResult
{
    public DateTimeImmutable $refreshedAt;

    public function __construct(
        public ReportingTimezone $reportingTimezone,
        public int $rowCount,
        DateTimeImmutable $refreshedAt,
    ) {
        if ($this->rowCount < 0) {
            throw new InvalidArgumentException(
                'Aggregate refresh row count cannot be negative.',
            );
        }

        $this->refreshedAt = $refreshedAt->setTimezone(
            new DateTimeZone('UTC'),
        );
    }
}
