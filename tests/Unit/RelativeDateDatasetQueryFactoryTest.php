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
use App\Analytics\Queries\Sources\AccountBalanceDatasetSource;
use App\Analytics\Queries\Sources\TransactionDatasetSource;
use App\Analytics\Time\RelativeDateDatasetQueryFactory;
use App\Analytics\Time\RelativeDateFilterFactory;
use App\Analytics\Time\RelativeDatePreset;
use App\Analytics\Time\RelativeDateRangeResolver;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RelativeDateDatasetQueryFactoryTest extends TestCase
{
    public function test_transaction_query_receives_previous_month_utc_boundaries(): void
    {
        $query = $this->factory()->create(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['transaction_reference'],
            measures: [],
            filters: [],
            relativeDateDimension: 'booked_at',
            preset: RelativeDatePreset::PREVIOUS_MONTH,
            now: $this->now(),
            reportingTimezone: $this->timezone(),
        );

        $compiled = $this->compiler()->compile(
            new TransactionDatasetSource,
            $query,
            DatasetRowScope::unrestricted(),
        );

        $this->assertStringContainsString(
            'transactions.booked_at >= ?',
            $compiled->sql,
        );

        $this->assertStringContainsString(
            'transactions.booked_at < ?',
            $compiled->sql,
        );

        $this->assertSame(
            [
                '2026-07-31T22:00:00+00:00',
                '2026-08-31T22:00:00+00:00',
                100,
            ],
            $compiled->bindings,
        );
    }

    public function test_single_day_snapshot_filter_satisfies_balance_context(): void
    {
        $query = $this->factory()->create(
            dataset: DatasetKey::ACCOUNT_BALANCES,
            dimensions: ['branch', 'currency'],
            measures: ['total_ledger_balance'],
            filters: [],
            relativeDateDimension: 'snapshot_date',
            preset: RelativeDatePreset::TODAY,
            now: $this->now(),
            reportingTimezone: $this->timezone(),
        );

        $compiled = $this->compiler()->compile(
            new AccountBalanceDatasetSource,
            $query,
            DatasetRowScope::unrestricted(),
        );

        $this->assertStringContainsString(
            'account_balance_snapshots.snapshot_date = ?',
            $compiled->sql,
        );

        $this->assertSame(
            ['2026-09-01', 100],
            $compiled->bindings,
        );
    }

    public function test_explicit_filters_are_preserved_before_relative_filters(): void
    {
        $currencyFilter = $this->filter(
            DatasetKey::TRANSACTIONS,
            'currency',
            'equals',
            'EUR',
        );

        $query = $this->factory()->create(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['currency'],
            measures: ['total_amount'],
            filters: [$currencyFilter],
            relativeDateDimension: 'booked_at',
            preset: RelativeDatePreset::TODAY,
            now: $this->now(),
            reportingTimezone: $this->timezone(),
        );

        $compiled = $this->compiler()->compile(
            new TransactionDatasetSource,
            $query,
            DatasetRowScope::unrestricted(),
        );

        $this->assertSame(
            [
                'EUR',
                '2026-08-31T22:00:00+00:00',
                '2026-09-01T22:00:00+00:00',
                100,
            ],
            $compiled->bindings,
        );
    }

    public function test_explicit_filter_cannot_target_relative_date_dimension(): void
    {
        $explicitDateFilter = $this->filter(
            DatasetKey::TRANSACTIONS,
            'booked_at',
            'after',
            '2026-08-01T00:00:00+00:00',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Explicit filter for [booked_at] cannot be combined with a relative date preset on the same dimension.',
        );

        $this->factory()->create(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['transaction_reference'],
            measures: [],
            filters: [$explicitDateFilter],
            relativeDateDimension: 'booked_at',
            preset: RelativeDatePreset::TODAY,
            now: $this->now(),
            reportingTimezone: $this->timezone(),
        );
    }

    private function factory(): RelativeDateDatasetQueryFactory
    {
        $registry = new DatasetRegistry;

        return new RelativeDateDatasetQueryFactory(
            new RelativeDateFilterFactory(
                $registry,
                new FilterValidator(
                    $registry,
                    new DimensionFilterRules,
                ),
                new RelativeDateRangeResolver,
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
        DatasetKey $dataset,
        string $dimension,
        string $operator,
        mixed $value,
    ): FilterCondition {
        return (new FilterValidator(
            new DatasetRegistry,
            new DimensionFilterRules,
        ))->validate(
            $dataset,
            $dimension,
            $operator,
            $value,
        );
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            '2026-09-01T12:00:00+02:00',
        );
    }

    private function timezone(): DateTimeZone
    {
        return new DateTimeZone('Europe/Luxembourg');
    }
}
