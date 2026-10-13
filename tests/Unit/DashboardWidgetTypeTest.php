<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardWidgetType;
use PHPUnit\Framework\TestCase;

final class DashboardWidgetTypeTest extends TestCase
{
    public function test_widget_types_have_stable_values(): void
    {
        $this->assertSame(
            [
                'kpi',
                'chart',
                'table',
            ],
            array_map(
                static fn (DashboardWidgetType $type): string => $type->value,
                DashboardWidgetType::cases(),
            ),
        );
    }
}
