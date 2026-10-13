<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardWidgetConfiguration;
use App\Analytics\Dashboards\DashboardWidgetType;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DashboardWidgetConfigurationTest extends TestCase
{
    public function test_widget_defaults_are_canonicalized(): void
    {
        $this->assertSame(
            [
                'show_comparison' => false,
            ],
            DashboardWidgetConfiguration::from(
                DashboardWidgetType::KPI,
                null,
            )->toArray(),
        );

        $this->assertNull(
            DashboardWidgetConfiguration::from(
                DashboardWidgetType::CHART,
                null,
            )->toArray(),
        );

        $this->assertSame(
            [
                'row_limit' => 25,
            ],
            DashboardWidgetConfiguration::from(
                DashboardWidgetType::TABLE,
                null,
            )->toArray(),
        );
    }

    public function test_explicit_values_are_canonicalized(): void
    {
        $this->assertSame(
            [
                'show_comparison' => true,
            ],
            DashboardWidgetConfiguration::from(
                DashboardWidgetType::KPI,
                [
                    'show_comparison' => true,
                ],
            )->toArray(),
        );

        $this->assertSame(
            [
                'row_limit' => 50,
            ],
            DashboardWidgetConfiguration::from(
                DashboardWidgetType::TABLE,
                [
                    'row_limit' => 50,
                ],
            )->toArray(),
        );
    }

    public function test_unknown_configuration_key_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        DashboardWidgetConfiguration::from(
            DashboardWidgetType::KPI,
            [
                'sql' => 'SELECT * FROM accounts',
            ],
        );
    }

    #[DataProvider('incompatibleConfigurationProvider')]
    public function test_configuration_for_another_widget_type_is_rejected(
        DashboardWidgetType $type,
        array $configuration,
    ): void {
        $this->expectException(
            InvalidArgumentException::class,
        );

        DashboardWidgetConfiguration::from(
            $type,
            $configuration,
        );
    }

    /**
     * @return iterable<
     *     string,
     *     array{DashboardWidgetType, array<string, mixed>}
     * >
     */
    public static function incompatibleConfigurationProvider(): iterable
    {
        yield 'table option on KPI' => [
            DashboardWidgetType::KPI,
            [
                'row_limit' => 25,
            ],
        ];

        yield 'KPI option on chart' => [
            DashboardWidgetType::CHART,
            [
                'show_comparison' => true,
            ],
        ];
    }

    #[DataProvider('invalidValueProvider')]
    public function test_invalid_configuration_value_is_rejected(
        DashboardWidgetType $type,
        array $configuration,
    ): void {
        $this->expectException(
            InvalidArgumentException::class,
        );

        DashboardWidgetConfiguration::from(
            $type,
            $configuration,
        );
    }

    /**
     * @return iterable<
     *     string,
     *     array{DashboardWidgetType, array<string, mixed>}
     * >
     */
    public static function invalidValueProvider(): iterable
    {
        yield 'comparison is not boolean' => [
            DashboardWidgetType::KPI,
            [
                'show_comparison' => 1,
            ],
        ];

        yield 'row limit is zero' => [
            DashboardWidgetType::TABLE,
            [
                'row_limit' => 0,
            ],
        ];

        yield 'row limit exceeds maximum' => [
            DashboardWidgetType::TABLE,
            [
                'row_limit' => 101,
            ],
        ];
    }
}
