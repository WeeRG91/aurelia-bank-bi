<?php

namespace Tests\Feature;

use App\Analytics\Dashboards\DashboardCacheGeneration;
use App\Analytics\Time\ReportingTimezone;
use App\Analytics\Warehouse\Aggregates\DailyTransactionAggregateRefresher;
use App\Analytics\Warehouse\Aggregates\DailyTransactionAggregateRefreshResult;
use DateTimeImmutable;
use Tests\TestCase;

final class RefreshDailyTransactionAggregateCommandTest extends TestCase
{
    public function test_configured_reporting_timezone_is_refreshed(): void
    {
        config([
            'analytics.reporting_timezone' => 'Europe/Luxembourg',
        ]);

        $refresher = new class implements DailyTransactionAggregateRefresher
        {
            public ?ReportingTimezone $reportingTimezone = null;

            public ?DateTimeImmutable $refreshedAt = null;

            public function refresh(
                ReportingTimezone $reportingTimezone,
                DateTimeImmutable $refreshedAt,
            ): DailyTransactionAggregateRefreshResult {
                $this->reportingTimezone = $reportingTimezone;
                $this->refreshedAt = $refreshedAt;

                return new DailyTransactionAggregateRefreshResult(
                    reportingTimezone: $reportingTimezone,
                    rowCount: 42,
                    refreshedAt: $refreshedAt,
                );
            }
        };

        $cacheGeneration = new class implements DashboardCacheGeneration
        {
            public int $rotationCount = 0;

            public function current(): string
            {
                return 'before-refresh';
            }

            public function rotate(): string
            {
                $this->rotationCount++;

                return 'after-refresh';
            }
        };

        $this->app->instance(
            DailyTransactionAggregateRefresher::class,
            $refresher,
        );

        $this->app->instance(
            DashboardCacheGeneration::class,
            $cacheGeneration,
        );

        $this->artisan(
            'warehouse:refresh-daily-transaction-aggregate',
        )
            ->expectsOutputToContain(
                'Daily transaction aggregate refreshed successfully: 42 rows for Europe/Luxembourg.',
            )
            ->assertSuccessful();

        $this->assertNotNull($refresher->reportingTimezone);
        $this->assertSame(
            'Europe/Luxembourg',
            $refresher->reportingTimezone->name,
        );

        $this->assertNotNull($refresher->refreshedAt);
        $this->assertSame(
            'UTC',
            $refresher->refreshedAt->getTimezone()->getName(),
        );

        $this->assertSame(
            1,
            $cacheGeneration->rotationCount,
        );
    }

    public function test_invalid_reporting_timezone_is_rejected(): void
    {
        config([
            'analytics.reporting_timezone' => 'Invalid/Timezone',
        ]);

        $this->artisan(
            'warehouse:refresh-daily-transaction-aggregate',
        )
            ->expectsOutputToContain(
                'Unknown reporting timezone [Invalid/Timezone].',
            )
            ->assertFailed();
    }
}
