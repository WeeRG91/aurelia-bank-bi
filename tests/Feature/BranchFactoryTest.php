<?php

namespace Tests\Feature;

use App\Models\Branch;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class BranchFactoryTest extends TestCase
{
    public function test_it_builds_a_coherent_synthetic_branch(): void
    {
        $branch = Branch::factory()->make();

        $expectedCountryByCity = [
            'Luxembourg' => 'LU',
            'Esch-sur-Alzette' => 'LU',
            'Metz' => 'FR',
            'Trier' => 'DE',
            'Arlon' => 'BE',
        ];

        $this->assertMatchesRegularExpression(
            '/^[A-Z]{2}-[A-Z]{3}-\d{3}$/',
            $branch->branch_code,
        );
        $this->assertArrayHasKey($branch->city, $expectedCountryByCity);
        $this->assertSame(
            $expectedCountryByCity[$branch->city],
            $branch->country_code,
        );
        $this->assertStringContainsString($branch->city, $branch->name);
        $this->assertInstanceOf(CarbonImmutable::class, $branch->opened_at);
    }
}
