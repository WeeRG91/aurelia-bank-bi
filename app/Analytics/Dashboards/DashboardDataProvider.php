<?php

namespace App\Analytics\Dashboards;

use App\Analytics\Datasets\DatasetFieldAccess;
use App\Analytics\Queries\AuthorizedDatasetQueryExecutor;
use App\Analytics\Queries\Sources\DatasetSourceRegistry;
use App\Analytics\Time\RelativeDatePreset;
use App\Analytics\Time\ReportingTimezone;
use App\Models\User;
use DateInvalidTimeZoneException;
use DateTimeImmutable;
use Throwable;

final readonly class DashboardDataProvider
{
    public function __construct(
        private DashboardWidgetRegistry $widgets,
        private DashboardWidgetQueryFactory $queryFactory,
        private DatasetFieldAccess $fieldAccess,
        private DatasetSourceRegistry $sources,
        private AuthorizedDatasetQueryExecutor $executor,
        private DashboardCacheKeyFactory $cacheKeys,
        private DashboardResultCache $cache,
    ) {}

    /**
     * @throws DateInvalidTimeZoneException
     * @throws Throwable
     */
    public function forUser(
        User $user,
        DateTimeImmutable $now,
        ReportingTimezone $reportingTimezone,
        DashboardPeriod $period,
    ): DashboardCacheEntry {
        $cacheKey = $this->cacheKeys->forUser(
            user: $user,
            period: $period,
            reportingTimezone: $reportingTimezone,
            now: $now,
        );

        if ($cacheKey !== null) {
            $cached = $this->cache->get($cacheKey);

            if ($cached !== null) {
                return $cached;
            }
        }

        $results = $this->loadWidgets(
            user: $user,
            now: $now,
            reportingTimezone: $reportingTimezone,
            relativeDatePreset: $period->relativeDatePreset(),
        );

        if ($cacheKey === null) {
            return new DashboardCacheEntry(
                widgets: $results,
                generatedAt: $now,
                cacheHit: false,
            );
        }

        return $this->cache->put(
            key: $cacheKey,
            results: $results,
            generatedAt: $now,
        );
    }

    /**
     * @return list<DashboardWidgetResult>
     *
     * @throws DateInvalidTimeZoneException
     * @throws Throwable
     */
    private function loadWidgets(
        User $user,
        DateTimeImmutable $now,
        ReportingTimezone $reportingTimezone,
        RelativeDatePreset $relativeDatePreset,
    ): array {
        $results = [];

        foreach ($this->widgets->all() as $widget) {
            $definition = $widget->reportDefinition(
                $relativeDatePreset,
            );

            if (
                ! $this->fieldAccess->canUseDefinition(
                    $user,
                    $widget->dataset,
                    $definition,
                )
            ) {
                continue;
            }

            $query = $this->queryFactory->create(
                widget: $widget,
                now: $now,
                reportingTimezone: $reportingTimezone,
                relativeDatePreset: $relativeDatePreset,
            );

            $rows = $this->executor->executeFor(
                $user,
                $this->sources->get($widget->dataset),
                $query,
            );

            $results[] = new DashboardWidgetResult(
                widget: $widget,
                period: $relativeDatePreset,
                rows: array_map(
                    static fn (object $row): array => (array) $row,
                    $rows,
                ),
            );
        }

        return $results;
    }
}
