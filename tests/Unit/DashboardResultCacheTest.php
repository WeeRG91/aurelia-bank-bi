<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardResultCache;
use App\Analytics\Dashboards\DashboardWidgetKey;
use App\Analytics\Dashboards\DashboardWidgetRegistry;
use App\Analytics\Dashboards\DashboardWidgetResult;
use App\Analytics\Time\RelativeDatePreset;
use DateTimeImmutable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Tests\TestCase;

final class DashboardResultCacheTest extends TestCase
{
    public function test_widget_results_can_be_cached_and_restored(): void
    {
        [$cache] = $this->cache();

        $generatedAt = new DateTimeImmutable(
            '2026-10-07T12:00:00+00:00',
        );

        $widget = (new DashboardWidgetRegistry)->get(
            DashboardWidgetKey::TRANSACTION_SUMMARY,
        );

        $cache->put(
            key: 'analytics:dashboard:test',
            results: [
                new DashboardWidgetResult(
                    widget: $widget,
                    period: RelativeDatePreset::LAST_30_DAYS,
                    rows: [
                        [
                            'currency' => 'EUR',
                            'transaction_count' => 25,
                        ],
                    ],
                ),
            ],
            generatedAt: $generatedAt,
        );

        $entry = $cache->get(
            'analytics:dashboard:test',
        );

        $this->assertNotNull($entry);
        $this->assertSame(
            $generatedAt->format(DATE_ATOM),
            $entry->generatedAt->format(DATE_ATOM),
        );
        $this->assertCount(1, $entry->widgets);
        $this->assertSame(
            DashboardWidgetKey::TRANSACTION_SUMMARY,
            $entry->widgets[0]->widget->key,
        );
        $this->assertSame(
            RelativeDatePreset::LAST_30_DAYS,
            $entry->widgets[0]->period,
        );
        $this->assertSame(
            [
                [
                    'currency' => 'EUR',
                    'transaction_count' => 25,
                ],
            ],
            $entry->widgets[0]->rows,
        );
    }

    public function test_missing_entry_returns_null(): void
    {
        [$cache] = $this->cache();

        $this->assertNull(
            $cache->get('analytics:dashboard:missing'),
        );
    }

    public function test_invalid_payload_is_rejected_and_removed(): void
    {
        [$cache, $repository] = $this->cache();

        $repository->put(
            'analytics:dashboard:invalid',
            [
                'version' => 999,
                'generated_at' => 'invalid',
                'widgets' => [],
            ],
            60,
        );

        $this->assertNull(
            $cache->get('analytics:dashboard:invalid'),
        );

        $this->assertFalse(
            $repository->has('analytics:dashboard:invalid'),
        );
    }

    /**
     * @return array{DashboardResultCache, Repository}
     */
    private function cache(): array
    {
        $repository = new Repository(
            new ArrayStore,
        );

        return [
            new DashboardResultCache(
                cache: $repository,
                widgets: new DashboardWidgetRegistry,
            ),
            $repository,
        ];
    }
}
