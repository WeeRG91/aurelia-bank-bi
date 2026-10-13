<?php

namespace App\Analytics\Dashboards;

use InvalidArgumentException;

final readonly class DashboardWidgetConfiguration
{
    /**
     * @param  array<string, bool|int>|null  $values
     */
    private function __construct(
        private ?array $values,
    ) {}

    /**
     * @param  array<string, mixed>|null  $configuration
     */
    public static function from(
        DashboardWidgetType $type,
        ?array $configuration,
    ): self {
        $configuration ??= [];

        return match ($type) {
            DashboardWidgetType::KPI => self::kpi(
                $configuration,
            ),
            DashboardWidgetType::CHART => self::chart(
                $configuration,
            ),
            DashboardWidgetType::TABLE => self::table(
                $configuration,
            ),
        };
    }

    /**
     * @return array<string, bool|int>|null
     */
    public function toArray(): ?array
    {
        return $this->values;
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private static function kpi(
        array $configuration,
    ): self {
        self::assertOnlyKeys(
            $configuration,
            ['show_comparison'],
        );

        $showComparison = $configuration['show_comparison']
            ?? false;

        if (! is_bool($showComparison)) {
            throw new InvalidArgumentException(
                'KPI comparison configuration must be boolean.',
            );
        }

        return new self([
            'show_comparison' => $showComparison,
        ]);
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private static function chart(
        array $configuration,
    ): self {
        self::assertOnlyKeys($configuration, []);

        return new self(null);
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private static function table(
        array $configuration,
    ): self {
        self::assertOnlyKeys(
            $configuration,
            ['row_limit'],
        );

        $rowLimit = $configuration['row_limit'] ?? 25;

        if (
            ! is_int($rowLimit)
            || $rowLimit < 1
            || $rowLimit > 100
        ) {
            throw new InvalidArgumentException(
                'Table row limit must be an integer between 1 and 100.',
            );
        }

        return new self([
            'row_limit' => $rowLimit,
        ]);
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @param  list<string>  $allowedKeys
     */
    private static function assertOnlyKeys(
        array $configuration,
        array $allowedKeys,
    ): void {
        $unknownKeys = array_diff(
            array_keys($configuration),
            $allowedKeys,
        );

        if ($unknownKeys !== []) {
            throw new InvalidArgumentException(
                'Dashboard widget configuration contains unsupported keys.',
            );
        }
    }
}
