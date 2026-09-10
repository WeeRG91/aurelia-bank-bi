<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardWidgetKey;
use PHPUnit\Framework\TestCase;

final class DashboardWidgetKeyTest extends TestCase
{
    public function test_dashboard_widget_keys_have_stable_values(): void
    {
        $this->assertSame(
            [
                'transaction_summary',
                'daily_cash_flow',
                'transaction_mix',
            ],
            array_column(DashboardWidgetKey::cases(), 'value'),
        );
    }
}
