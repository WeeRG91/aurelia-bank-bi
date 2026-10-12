<?php

namespace App\Analytics\Dashboards;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;

final readonly class RepositoryDashboardCacheGeneration implements DashboardCacheGeneration
{
    private const string KEY = 'analytics:dashboard:generation:v1';

    public function __construct(
        private Repository $cache,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function current(): string
    {
        $generation = $this->cache->get(self::KEY);

        return is_string($generation) && $generation !== ''
            ? $generation
            : 'initial';
    }

    public function rotate(): string
    {
        $generation = (string) Str::uuid();

        $this->cache->forever(
            self::KEY,
            $generation,
        );

        return $generation;
    }
}
