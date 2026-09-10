<?php

namespace App\Analytics\Dashboards;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Time\RelativeDatePreset;
use LogicException;

final class DashboardWidgetRegistry
{
    /**
     * @var array<string, DashboardWidgetDefinition>
     */
    private array $definitions = [];

    public function __construct(
        ?iterable $definitions = null,
    ) {
        foreach (
            $definitions ?? $this->defaultDefinitions() as $definition
        ) {
            $key = $definition->key->value;

            if (isset($this->definitions[$key])) {
                throw new LogicException(
                    "Duplicate dashboard widget [{$key}].",
                );
            }

            $this->definitions[$key] = $definition;
        }
    }

    /**
     * @return list<DashboardWidgetDefinition>
     */
    public function all(): array
    {
        return array_values($this->definitions);
    }

    public function find(
        DashboardWidgetKey|string $identifier,
    ): ?DashboardWidgetDefinition {
        $key = $identifier instanceof DashboardWidgetKey
            ? $identifier
            : DashboardWidgetKey::tryFrom($identifier);

        if ($key === null) {
            return null;
        }

        return $this->definitions[$key->value] ?? null;
    }

    public function get(
        DashboardWidgetKey|string $identifier,
    ): DashboardWidgetDefinition {
        $value = $identifier instanceof DashboardWidgetKey
            ? $identifier->value
            : $identifier;

        return $this->find($identifier)
            ?? throw new LogicException(
                "Unknown dashboard widget [{$value}].",
            );
    }

    private function defaultDefinitions(): array
    {
        return [
            new DashboardWidgetDefinition(
                key: DashboardWidgetKey::TRANSACTION_SUMMARY,
                label: 'Transaction summary',
                description: 'Transaction volume and cash flow by currency over the last 30 days.',
                dataset: DatasetKey::TRANSACTIONS,
                dimensions: ['currency'],
                measures: [
                    'transaction_count',
                    'incoming_amount',
                    'outgoing_amount',
                    'net_cash_flow',
                ],
                relativeDateDimension: 'booking_date',
                relativeDatePreset: RelativeDatePreset::LAST_30_DAYS,
                limit: 20,
            ),
            new DashboardWidgetDefinition(
                key: DashboardWidgetKey::DAILY_CASH_FLOW,
                label: 'Daily cash flow',
                description: 'Daily incoming, outgoing, and net transaction activity over the last 30 days.',
                dataset: DatasetKey::TRANSACTIONS,
                dimensions: [
                    'booking_date',
                    'currency',
                ],
                measures: [
                    'transaction_count',
                    'incoming_amount',
                    'outgoing_amount',
                    'net_cash_flow',
                ],
                relativeDateDimension: 'booking_date',
                relativeDatePreset: RelativeDatePreset::LAST_30_DAYS,
                limit: 500,
            ),
            new DashboardWidgetDefinition(
                key: DashboardWidgetKey::TRANSACTION_MIX,
                label: 'Transaction mix',
                description: 'Transaction volume by transaction type over the last 30 days.',
                dataset: DatasetKey::TRANSACTIONS,
                dimensions: ['transaction_type'],
                measures: ['transaction_count'],
                relativeDateDimension: 'booking_date',
                relativeDatePreset: RelativeDatePreset::LAST_30_DAYS,
                limit: 50,
            ),
        ];
    }
}
