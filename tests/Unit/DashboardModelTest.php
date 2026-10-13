<?php

namespace Tests\Unit;

use App\Models\Dashboard;
use App\Models\Employee;
use Tests\TestCase;

final class DashboardModelTest extends TestCase
{
    public function test_dashboard_casts_and_relationships_are_configured(): void
    {
        $dashboard = new Dashboard;

        $dashboard->forceFill([
            'is_default' => 1,
        ]);

        $this->assertTrue($dashboard->is_default);

        $this->assertInstanceOf(
            Employee::class,
            $dashboard->owner()->getRelated(),
        );

        $this->assertSame(
            'owner_employee_id',
            $dashboard->owner()->getForeignKeyName(),
        );

        $this->assertSame(
            'dashboard_id',
            $dashboard->widgets()->getForeignKeyName(),
        );
    }

    public function test_widgets_have_deterministic_document_order(): void
    {
        $dashboard = new Dashboard;

        $orders = $dashboard
            ->widgets()
            ->getQuery()
            ->getQuery()
            ->orders;

        $this->assertSame(
            [
                [
                    'column' => 'grid_row',
                    'direction' => 'asc',
                ],
                [
                    'column' => 'grid_column',
                    'direction' => 'asc',
                ],
                [
                    'column' => 'id',
                    'direction' => 'asc',
                ],
            ],
            $orders,
        );
    }
}
