<?php

namespace App\Analytics\Warehouse\Aggregates;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Filters\FilterOperator;
use App\Analytics\Queries\DatasetQuery;

final readonly class DailyTransactionAggregateEligibility
{
    /**
     * @var list<string>
     */
    private const array SUPPORTED_DIMENSIONS = [
        'branch',
        'country',
        'transaction_type',
        'currency',
        'booking_date',
    ];

    /**
     * @var list<string>
     */
    private const array SUPPORTED_MEASURES = [
        'transaction_count',
        'total_amount',
        'incoming_amount',
        'outgoing_amount',
        'net_cash_flow',
    ];

    public function supports(DatasetQuery $query): bool
    {
        if ($query->dataset !== DatasetKey::TRANSACTIONS) {
            return false;
        }

        if ($query->measures === []) {
            return false;
        }

        if (
            array_diff(
                $query->dimensions,
                self::SUPPORTED_DIMENSIONS,
            ) !== []
        ) {
            return false;
        }

        if (
            array_diff(
                $query->measures,
                self::SUPPORTED_MEASURES,
            ) !== []
        ) {
            return false;
        }

        foreach ($query->filters as $filter) {
            if (
                ! in_array(
                    $filter->dimension,
                    self::SUPPORTED_DIMENSIONS,
                    true,
                )
            ) {
                return false;
            }
        }

        return $this->hasBookedDateConstraint($query);
    }

    private function hasBookedDateConstraint(
        DatasetQuery $query,
    ): bool {
        foreach ($query->filters as $filter) {
            if (
                $filter->dimension === 'booking_date'
                && $filter->operator !== FilterOperator::IS_NULL
            ) {
                return true;
            }
        }

        return false;
    }
}
