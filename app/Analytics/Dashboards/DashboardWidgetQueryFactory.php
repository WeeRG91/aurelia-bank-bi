<?php

namespace App\Analytics\Dashboards;

use App\Analytics\Queries\DatasetQuery;
use App\Analytics\Queries\ReportDefinitionQueryFactory;
use App\Analytics\Time\RelativeDatePreset;
use App\Analytics\Time\ReportingTimezone;
use DateInvalidTimeZoneException;
use DateTimeImmutable;

final readonly class DashboardWidgetQueryFactory
{
    public function __construct(
        private ReportDefinitionQueryFactory $queryFactory,
    ) {}

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function create(
        DashboardWidgetDefinition $widget,
        DateTimeImmutable $now,
        ReportingTimezone $reportingTimezone,
        ?RelativeDatePreset $relativeDatePreset = null,
    ): DatasetQuery {
        return $this->queryFactory->create(
            dataset: $widget->dataset,
            definition: $widget->reportDefinition(
                $relativeDatePreset,
            ),
            now: $now,
            reportingTimezone: $reportingTimezone,
        );
    }
}
