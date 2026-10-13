<?php

namespace App\Analytics\Dashboards;

use DateTimeImmutable;

final readonly class EmployeeDashboardCacheEntry
{
    /**
     * @param  list<array<string, mixed>>  $widgets
     */
    public function __construct(
        public array $widgets,
        public DateTimeImmutable $generatedAt,
        public bool $cacheHit,
    ) {}
}
