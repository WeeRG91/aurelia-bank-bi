<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardPeriod;
use App\Http\Requests\DashboardRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class DashboardRequestTest extends TestCase
{
    public function test_valid_period_is_resolved(): void
    {
        $request = DashboardRequest::create(
            '/dashboard',
            'GET',
            ['period' => 'last_7_days'],
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules(),
        );

        $this->assertTrue($validator->passes());

        $request->setValidator($validator);

        $this->assertSame(
            DashboardPeriod::LAST_7_DAYS,
            $request->period(),
        );
    }

    public function test_missing_period_uses_default(): void
    {
        $request = DashboardRequest::create(
            '/dashboard',
            'GET',
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules(),
        );

        $this->assertTrue($validator->passes());

        $request->setValidator($validator);

        $this->assertSame(
            DashboardPeriod::LAST_30_DAYS,
            $request->period(),
        );
    }

    public function test_arbitrary_period_is_rejected(): void
    {
        $request = DashboardRequest::create(
            '/dashboard',
            'GET',
            ['period' => 'all_time'],
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules(),
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'period',
            $validator->errors()->toArray(),
        );
    }
}
