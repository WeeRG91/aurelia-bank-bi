<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardWidgetKey;
use App\Analytics\Dashboards\DashboardWidgetRegistry;
use App\Analytics\Dashboards\DashboardWidgetResult;
use App\Analytics\Time\RelativeDatePreset;
use PHPUnit\Framework\TestCase;

final class DashboardWidgetResultTest extends TestCase
{
    public function test_widget_result_has_a_stable_frontend_payload(): void
    {
        $widget = (new DashboardWidgetRegistry)->get(
            DashboardWidgetKey::TRANSACTION_SUMMARY,
        );

        $result = new DashboardWidgetResult(
            widget: $widget,
            period: RelativeDatePreset::LAST_30_DAYS,
            rows: [
                [
                    'currency' => 'EUR',
                    'transaction_count' => 25,
                    'incoming_amount' => '1250.50',
                    'outgoing_amount' => '400.25',
                    'net_cash_flow' => '850.25',
                ],
            ],
        );

        $this->assertSame(
            [
                'key' => 'transaction_summary',
                'label' => 'Transaction summary',
                'description' => 'Transaction volume and cash flow by currency over the last 30 days.',
                'dataset' => 'transactions',
                'period' => 'last_30_days',
                'rows' => [
                    [
                        'currency' => 'EUR',
                        'transaction_count' => 25,
                        'incoming_amount' => '1250.50',
                        'outgoing_amount' => '400.25',
                        'net_cash_flow' => '850.25',
                    ],
                ],
            ],
            $result->toArray(),
        );
    }
}
