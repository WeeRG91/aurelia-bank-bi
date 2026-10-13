<?php

namespace App\Analytics\Dashboards;

use DateTimeImmutable;
use Exception;
use Illuminate\Contracts\Cache\Repository;
use Psr\SimpleCache\InvalidArgumentException;

final readonly class EmployeeDashboardResultCache
{
    private const int PAYLOAD_VERSION = 1;

    public function __construct(
        private Repository $cache,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function get(
        string $key,
    ): ?EmployeeDashboardCacheEntry {
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
     * @param  list<array<string, mixed>>  $widgets
     */
    public function put(
        string $key,
        array $widgets,
        DateTimeImmutable $generatedAt,
    ): EmployeeDashboardCacheEntry {
        $entry = new EmployeeDashboardCacheEntry(
            widgets: $widgets,
            generatedAt: $generatedAt,
            cacheHit: false,
        );

        $this->cache->put(
            $key,
            [
                'version' => self::PAYLOAD_VERSION,
                'generated_at' => $generatedAt->format(DATE_ATOM),
                'widgets' => $widgets,
            ],
            max(
                1,
                (int) config(
                    'analytics.dashboard_cache_ttl_seconds',
                    300,
                ),
            ),
        );

        return $entry;
    }

    private function hydrate(
        mixed $payload,
    ): ?EmployeeDashboardCacheEntry {
        if (
            ! is_array($payload)
            || ($payload['version'] ?? null) !== self::PAYLOAD_VERSION
            || ! is_string($payload['generated_at'] ?? null)
            || ! is_array($payload['widgets'] ?? null)
            || ! array_is_list($payload['widgets'])
            || ! $this->containsOnlyWidgetPayloads(
                $payload['widgets'],
            )
        ) {
            return null;
        }

        try {
            $generatedAt = new DateTimeImmutable(
                $payload['generated_at'],
            );
        } catch (Exception) {
            return null;
        }

        return new EmployeeDashboardCacheEntry(
            widgets: $payload['widgets'],
            generatedAt: $generatedAt,
            cacheHit: true,
        );
    }

    private function containsOnlyWidgetPayloads(
        array $widgets,
    ): bool {
        foreach ($widgets as $widget) {
            if (
                ! is_array($widget)
                || ! is_int($widget['widgetId'] ?? null)
                || ! is_string($widget['type'] ?? null)
                || ! is_string($widget['status'] ?? null)
                || ! $this->isSafeValue($widget)
            ) {
                return false;
            }
        }

        return true;
    }

    private function isSafeValue(
        mixed $value,
    ): bool {
        if (
            $value === null
            || is_bool($value)
            || is_int($value)
            || is_float($value)
            || is_string($value)
        ) {
            return true;
        }

        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $key => $nestedValue) {
            if (
                ! is_int($key)
                && ! is_string($key)
            ) {
                return false;
            }

            if (! $this->isSafeValue($nestedValue)) {
                return false;
            }
        }

        return true;
    }
}
