<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\RepositoryDashboardCacheGeneration;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;

final class RepositoryDashboardCacheGenerationTest extends TestCase
{
    public function test_generation_can_be_rotated(): void
    {
        $generation = new RepositoryDashboardCacheGeneration(
            new Repository(new ArrayStore),
        );

        $this->assertSame(
            'initial',
            $generation->current(),
        );

        $rotated = $generation->rotate();

        $this->assertNotSame('initial', $rotated);
        $this->assertSame($rotated, $generation->current());
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f-]{36}$/',
            $rotated,
        );
    }
}
