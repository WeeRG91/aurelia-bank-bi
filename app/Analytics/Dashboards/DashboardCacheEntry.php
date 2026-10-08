<?php

namespace App\Analytics\Dashboards;

use DateTimeImmutable;

final readonly class DashboardCacheEntry
{
    /**
     * @param  list<DashboardWidgetResult>  $widgets
     */
    public function __construct(
        public array $widgets,
        public DateTimeImmutable $generatedAt,
        public bool $cacheHit,
    ) {}
}
