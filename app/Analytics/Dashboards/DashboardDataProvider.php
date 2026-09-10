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
    ) {}

    /**
     * @throws DateInvalidTimeZoneException
     * @throws Throwable
     */
    public function forUser(
        User $user,
        DateTimeImmutable $now,
        ReportingTimezone $reportingTimezone,
        ?RelativeDatePreset $relativeDatePreset = null,
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
                period: $relativeDatePreset
                    ?? $widget->relativeDatePreset,
                rows: array_map(
                    static fn (object $row): array => (array) $row,
                    $rows,
                ),
            );
        }

        return $results;
    }
}
