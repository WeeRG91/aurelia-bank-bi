<?php

namespace App\Analytics\Queries\Sources;

use App\Analytics\Queries\DatasetQuery;
use App\Analytics\Warehouse\Aggregates\DailyTransactionAggregateEligibility;

final readonly class TransactionDatasetSourceRouter
{
    public function __construct(
        private DailyTransactionAggregateEligibility $eligibility = new DailyTransactionAggregateEligibility,
        private DailyTransactionAggregateDatasetSource $aggregateSource = new DailyTransactionAggregateDatasetSource,
    ) {}

    public function route(
        DatasetSource $fallback,
        DatasetQuery $query,
    ): DatasetSource {
        if (! $fallback instanceof WarehouseTransactionDatasetSource) {
            return $fallback;
        }

        if (! $this->hasConfiguredTimezone($query)) {
            return $fallback;
        }

        if (! $this->eligibility->supports($query)) {
            return $fallback;
        }

        return $this->aggregateSource;
    }

    private function hasConfiguredTimezone(
        DatasetQuery $query,
    ): bool {
        $configuredTimezone = config(
            'analytics.reporting_timezone',
        );

        return is_string($configuredTimezone)
            && $configuredTimezone !== ''
            && $query->reportingTimezone->name === $configuredTimezone;
    }
}
