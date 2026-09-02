<?php

namespace App\Analytics\Queries;

use App\Analytics\Time\DateRange;
use App\Analytics\Time\PeriodComparison;
use InvalidArgumentException;

final readonly class DatasetQueryComparison
{
    public function __construct(
        public DatasetQuery $current,
        public DatasetQuery $comparison,
        public DateRange $currentRange,
        public DateRange $comparisonRange,
        public PeriodComparison $comparisonPeriod,
    ) {
        if ($this->current->dataset !== $this->comparison->dataset) {
            throw new InvalidArgumentException(
                'Comparison queries must target the same dataset.',
            );
        }

        if (
            $this->current->dimensions
            !== $this->comparison->dimensions
            || $this->current->measures
            !== $this->comparison->measures
        ) {
            throw new InvalidArgumentException(
                'Comparison queries must use identical dimensions and measures.',
            );
        }

        if (
            $this->current->reportingTimezone->name
            !== $this->comparison->reportingTimezone->name
        ) {
            throw new InvalidArgumentException(
                'Comparison queries must use the same reporting timezone.',
            );
        }
    }
}
