<?php

namespace App\Console\Commands;

use App\Analytics\Dashboards\DashboardCacheGeneration;
use App\Analytics\Time\ReportingTimezone;
use App\Analytics\Warehouse\Aggregates\DailyTransactionAggregateRefresher;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('warehouse:refresh-daily-transaction-aggregate')]
#[Description('Refresh the daily transaction activity aggregate')]
final class RefreshDailyTransactionAggregate extends Command
{
    public function __construct(
        private readonly DailyTransactionAggregateRefresher $refresher,
        private readonly DashboardCacheGeneration $cacheGeneration,
    ) {
        parent::__construct();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function handle(): int
    {
        try {
            $reportingTimezone = $this->reportingTimezone();
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $result = $this->refresher->refresh(
            $reportingTimezone,
            new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC'),
            ),
        );

        $this->cacheGeneration->rotate();

        $this->components->info(
            sprintf(
                'Daily transaction aggregate refreshed successfully: %d rows for %s.',
                $result->rowCount,
                $result->reportingTimezone->name,
            ),
        );

        return self::SUCCESS;
    }

    private function reportingTimezone(): ReportingTimezone
    {
        $value = config('analytics.reporting_timezone');

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(
                'Configuration [analytics.reporting_timezone] must contain a valid timezone.',
            );
        }

        return new ReportingTimezone($value);
    }
}
