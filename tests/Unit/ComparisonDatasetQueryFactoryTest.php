<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Filters\DimensionFilterRules;
use App\Analytics\Filters\FilterCondition;
use App\Analytics\Filters\FilterValidator;
use App\Analytics\Queries\Authorization\DatasetRowScope;
use App\Analytics\Queries\Compilation\FilterCompiler;
use App\Analytics\Queries\DatasetQueryCompiler;
use App\Analytics\Queries\Sources\TransactionDatasetSource;
use App\Analytics\Time\ComparisonDatasetQueryFactory;
use App\Analytics\Time\ComparisonDateRangeResolver;
use App\Analytics\Time\PeriodComparison;
use App\Analytics\Time\RelativeDateFilterFactory;
use App\Analytics\Time\RelativeDatePreset;
use App\Analytics\Time\RelativeDateRangeResolver;
use App\Analytics\Time\ReportingTimezone;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ComparisonDatasetQueryFactoryTest extends TestCase
{
    public function test_previous_period_builds_comparable_transaction_queries(): void
    {
        $currencyFilter = $this->filter(
            'currency',
            'equals',
            'EUR',
        );

        $pair = $this->factory()->create(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['booking_month'],
            measures: ['total_amount'],
            filters: [$currencyFilter],
            relativeDateDimension: 'booked_at',
            preset: RelativeDatePreset::PREVIOUS_MONTH,
            comparisonPeriod: PeriodComparison::PREVIOUS_PERIOD,
            now: $this->now(),
            reportingTimezone: $this->timezone(),
        );

        $this->assertSame(
            '2026-08-01',
            $pair->currentRange->startDate,
        );
        $this->assertSame(
            '2026-08-31',
            $pair->currentRange->endDate,
        );
        $this->assertSame(
            '2026-07-01',
            $pair->comparisonRange->startDate,
        );
        $this->assertSame(
            '2026-07-31',
            $pair->comparisonRange->endDate,
        );

        $this->assertSame(
            $pair->current->dimensions,
            $pair->comparison->dimensions,
        );
        $this->assertSame(
            $pair->current->measures,
            $pair->comparison->measures,
        );
        $this->assertSame(
            $currencyFilter,
            $pair->current->filters[0],
        );
        $this->assertSame(
            $currencyFilter,
            $pair->comparison->filters[0],
        );

        $current = $this->compiler()->compile(
            new TransactionDatasetSource,
            $pair->current,
            DatasetRowScope::unrestricted(),
        );

        $comparison = $this->compiler()->compile(
            new TransactionDatasetSource,
            $pair->comparison,
            DatasetRowScope::unrestricted(),
        );

        $this->assertSame(
            [
                'EUR',
                '2026-07-31T22:00:00+00:00',
                '2026-08-31T22:00:00+00:00',
                100,
            ],
            $current->bindings,
        );

        $this->assertSame(
            [
                'EUR',
                '2026-06-30T22:00:00+00:00',
                '2026-07-31T22:00:00+00:00',
                100,
            ],
            $comparison->bindings,
        );
    }

    public function test_explicit_filter_cannot_target_comparison_dimension(): void
    {
        $dateFilter = $this->filter(
            'booked_at',
            'after',
            '2026-08-01T00:00:00+00:00',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Explicit filter for [booked_at] cannot be combined with a period comparison on the same dimension.',
        );

        $this->factory()->create(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['booking_month'],
            measures: ['transaction_count'],
            filters: [$dateFilter],
            relativeDateDimension: 'booked_at',
            preset: RelativeDatePreset::PREVIOUS_MONTH,
            comparisonPeriod: PeriodComparison::PREVIOUS_PERIOD,
            now: $this->now(),
            reportingTimezone: $this->timezone(),
        );
    }

    private function factory(): ComparisonDatasetQueryFactory
    {
        $registry = new DatasetRegistry;
        $rangeResolver = new RelativeDateRangeResolver;

        return new ComparisonDatasetQueryFactory(
            $rangeResolver,
            new ComparisonDateRangeResolver,
            new RelativeDateFilterFactory(
                $registry,
                new FilterValidator(
                    $registry,
                    new DimensionFilterRules,
                ),
                $rangeResolver,
            ),
        );
    }

    private function compiler(): DatasetQueryCompiler
    {
        return new DatasetQueryCompiler(
            new FilterCompiler,
            new DatasetRegistry,
        );
    }

    private function filter(
        string $dimension,
        string $operator,
        mixed $value,
    ): FilterCondition {
        return (new FilterValidator(
            new DatasetRegistry,
            new DimensionFilterRules,
        ))->validate(
            DatasetKey::TRANSACTIONS,
            $dimension,
            $operator,
            $value,
        );
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            '2026-09-15T12:00:00+02:00',
        );
    }

    private function timezone(): ReportingTimezone
    {
        return new ReportingTimezone('Europe/Luxembourg');
    }
}
