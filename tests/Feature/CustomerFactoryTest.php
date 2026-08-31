<?php

namespace Tests\Feature;

use App\Enums\CustomerSegment;
use App\Enums\CustomerStatus;
use App\Enums\RiskLevel;
use App\Models\Customer;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class CustomerFactoryTest extends TestCase
{
    public function test_it_builds_a_coherent_synthetic_customer(): void
    {
        $customer = Customer::factory()->make();

        $this->assertMatchesRegularExpression(
            '/^CUS-\d{8}$/',
            $customer->customer_number,
        );
        $this->assertInstanceOf(CarbonImmutable::class, $customer->birth_date);
        $this->assertInstanceOf(CarbonImmutable::class, $customer->joined_at);
        $this->assertTrue(
            $customer->birth_date->lessThanOrEqualTo($customer->joined_at),
        );
        $this->assertInstanceOf(CustomerSegment::class, $customer->customer_segment);
        $this->assertInstanceOf(RiskLevel::class, $customer->risk_level);
        $this->assertInstanceOf(CustomerStatus::class, $customer->status);

        if ($customer->annual_income === null) {
            $this->assertNull($customer->annual_income_currency);
        } else {
            $this->assertMatchesRegularExpression(
                '/^\d+\.\d{2}$/',
                $customer->annual_income,
            );
            $this->assertMatchesRegularExpression(
                '/^[A-Z]{3}$/',
                $customer->annual_income_currency,
            );
        }
    }
}
