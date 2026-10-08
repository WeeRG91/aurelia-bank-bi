<?php

namespace App\Analytics\Dashboards;

use App\Analytics\Time\RelativeDatePreset;
use DateTimeImmutable;
use Exception;
use Illuminate\Contracts\Cache\Repository;
use Psr\SimpleCache\InvalidArgumentException;

final readonly class DashboardResultCache
{
    private const int PAYLOAD_VERSION = 1;

    public function __construct(
        private Repository $cache,
        private DashboardWidgetRegistry $widgets,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function get(string $key): ?DashboardCacheEntry
    {
        $payload = $this->cache->get($key);

        if ($payload === null) {
            return null;
        }

        $entry = $this->hydrate($payload);

        if ($entry === null) {
            $this->cache->forget($key);
        }

        return $entry;
    }

    /**
     * @param  list<DashboardWidgetResult>  $results
     */
    public function put(
        string $key,
        array $results,
        DateTimeImmutable $generatedAt,
    ): DashboardCacheEntry {
        $entry = new DashboardCacheEntry(
            widgets: $results,
            generatedAt: $generatedAt,
            cacheHit: false,
        );

        $this->cache->put(
            $key,
            [
                'version' => self::PAYLOAD_VERSION,
                'generated_at' => $generatedAt->format(DATE_ATOM),
                'widgets' => array_map(
                    static fn (
                        DashboardWidgetResult $result,
                    ): array => [
                        'key' => $result->widget->key->value,
                        'period' => $result->period->value,
                        'rows' => $result->rows,
                    ],
                    $results,
                ),
            ],
            max(
                1,
                (int) config(
                    'analytics.dashboard_cache_ttl_seconds',
                    300,
                ),
            )
        );

        return $entry;
    }

    private function hydrate(mixed $payload): ?DashboardCacheEntry
    {
        if (
            ! is_array($payload) ||
            ($payload['version'] ?? null) !== self::PAYLOAD_VERSION ||
            ! is_string($payload['generated_at'] ?? null) ||
            ! is_array($payload['widgets'] ?? null) ||
            ! array_is_list($payload['widgets'])
        ) {
            return null;
        }

        try {
            $generatedAt = new DateTimeImmutable($payload['generated_at']);
        } catch (Exception) {
            return null;
        }

        $results = [];

        foreach ($payload['widgets'] as $cachedWidget) {
            if (
                ! is_array($cachedWidget) ||
                ! is_string($cachedWidget['key'] ?? null) ||
                ! is_string($cachedWidget['period'] ?? null) ||
                ! is_array($cachedWidget['rows'] ?? null) ||
                ! array_is_list($cachedWidget['rows'])
            ) {
                return null;
            }

            $widget = $this->widgets->find($cachedWidget['key']);

            $period = RelativeDatePreset::tryFrom(
                $cachedWidget['period'],
            );

            if (
                $widget === null ||
                $period === null ||
                ! $this->containsOnlyArrayRows($cachedWidget['rows'])
            ) {
                return null;
            }

            $results[] = new DashboardWidgetResult(
                widget: $widget,
                period: $period,
                rows: $cachedWidget['rows'],
            );
        }

        return new DashboardCacheEntry(
            widgets: $results,
            generatedAt: $generatedAt,
            cacheHit: true,
        );
    }

    private function containsOnlyArrayRows(array $rows): bool
    {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                return false;
            }
        }

        return true;
    }
}
