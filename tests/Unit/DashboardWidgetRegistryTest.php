<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardWidgetDefinition;
use App\Analytics\Dashboards\DashboardWidgetKey;
use App\Analytics\Dashboards\DashboardWidgetRegistry;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Time\RelativeDatePreset;
use LogicException;
use PHPUnit\Framework\TestCase;

final class DashboardWidgetRegistryTest extends TestCase
{
    public function test_registry_contains_the_transaction_dashboard_widgets(): void
    {
        $definitions = (new DashboardWidgetRegistry)->all();

        $this->assertSame(
            DashboardWidgetKey::cases(),
            array_map(
                static fn (
                    DashboardWidgetDefinition $definition,
                ): DashboardWidgetKey => $definition->key,
                $definitions,
            ),
        );

        foreach ($definitions as $definition) {
            $this->assertSame(
                DatasetKey::TRANSACTIONS,
                $definition->dataset,
            );

            $this->assertSame(
                RelativeDatePreset::LAST_30_DAYS,
                $definition->relativeDatePreset,
            );

            $this->assertNotSame('', trim($definition->label));
            $this->assertNotSame('', trim($definition->description));
        }
    }

    public function test_registry_resolves_enum_and_string_keys(): void
    {
        $registry = new DashboardWidgetRegistry;

        $this->assertSame(
            DashboardWidgetKey::TRANSACTION_SUMMARY,
            $registry
                ->get(DashboardWidgetKey::TRANSACTION_SUMMARY)
                ->key,
        );

        $this->assertSame(
            DashboardWidgetKey::DAILY_CASH_FLOW,
            $registry->get('daily_cash_flow')->key,
        );

        $this->assertNull($registry->find('unknown_widget'));
    }

    public function test_dashboard_measures_have_required_context_dimensions(): void
    {
        $registry = new DashboardWidgetRegistry;

        $this->assertContains(
            'currency',
            $registry
                ->get(DashboardWidgetKey::TRANSACTION_SUMMARY)
                ->dimensions,
        );

        $this->assertContains(
            'currency',
            $registry
                ->get(DashboardWidgetKey::DAILY_CASH_FLOW)
                ->dimensions,
        );
    }

    public function test_duplicate_widget_registration_is_rejected(): void
    {
        $definition = (new DashboardWidgetRegistry)
            ->get(DashboardWidgetKey::TRANSACTION_SUMMARY);

        $this->expectException(LogicException::class);

        new DashboardWidgetRegistry([
            $definition,
            $definition,
        ]);
    }
}
