<?php

namespace Tests\Unit;

use App\Enums\CustomerSegment;
use App\Enums\CustomerStatus;
use App\Enums\RiskLevel;
use PHPUnit\Framework\TestCase;

class CustomerEnumsTest extends TestCase
{
    public function test_customer_segments_have_stable_values(): void
    {
        $this->assertSame(
            ['retail', 'premium', 'private_banking', 'business'],
            array_column(CustomerSegment::cases(), 'value'),
        );
    }

    public function test_risk_levels_have_stable_values(): void
    {
        $this->assertSame(
            ['low', 'medium', 'high'],
            array_column(RiskLevel::cases(), 'value'),
        );
    }

    public function test_customer_statuses_have_stable_values(): void
    {
        $this->assertSame(
            ['active', 'inactive', 'suspended', 'closed'],
            array_column(CustomerStatus::cases(), 'value'),
        );
    }
}
