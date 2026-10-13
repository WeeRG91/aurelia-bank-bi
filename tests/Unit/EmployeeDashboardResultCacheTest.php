<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\EmployeeDashboardResultCache;
use DateTimeImmutable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Tests\TestCase;

final class EmployeeDashboardResultCacheTest extends TestCase
{
    public function test_widget_payloads_can_be_cached_and_restored(): void
    {
        $repository = new Repository(
            new ArrayStore,
        );

        $cache = new EmployeeDashboardResultCache(
            $repository,
        );

        $generatedAt = new DateTimeImmutable(
            '2026-10-13T12:00:00+00:00',
        );

        $widgets = [
            [
                'widgetId' => 70,
                'type' => 'table',
                'status' => 'available',
                'dataset' => 'transactions',
                'dimensions' => ['currency'],
                'measures' => ['transaction_count'],
                'visualization' => null,
                'rowCount' => 1,
                'rows' => [
                    [
                        'currency' => 'EUR',
                        'transaction_count' => 25,
                    ],
                ],
                'valueCount' => 0,
                'values' => [],
                'comparisonCount' => 0,
                'comparisons' => [],
            ],
        ];

        $stored = $cache->put(
            key: 'analytics:employee-dashboard:test',
            widgets: $widgets,
            generatedAt: $generatedAt,
        );

        $this->assertFalse($stored->cacheHit);

        $restored = $cache->get(
            'analytics:employee-dashboard:test',
        );

        $this->assertNotNull($restored);
        $this->assertTrue($restored->cacheHit);
        $this->assertSame($widgets, $restored->widgets);
        $this->assertSame(
            $generatedAt->format(DATE_ATOM),
            $restored->generatedAt->format(DATE_ATOM),
        );
    }
}
