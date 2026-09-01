<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Filters\DimensionFilterRules;
use App\Analytics\Filters\FilterOperator;
use App\Analytics\Filters\FilterValidator;
use App\Analytics\Queries\Compilation\FilterCompiler;
use App\Analytics\Queries\Sources\TransactionDatasetSource;
use App\Analytics\Time\RelativeDateFilterFactory;
use App\Analytics\Time\RelativeDatePreset;
use App\Analytics\Time\RelativeDateRangeResolver;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RelativeDateFilterFactoryTest extends TestCase
{
    public function test_single_day_date_period_uses_equality(): void
    {
        $filters = $this->factory()->create(
            DatasetKey::ACCOUNT_BALANCES,
            'snapshot_date',
            RelativeDatePreset::TODAY,
            $this->now(),
            $this->timezone(),
        );

        $this->assertCount(1, $filters);
        $this->assertSame(
            FilterOperator::EQUALS,
            $filters[0]->operator,
        );
        $this->assertSame('2026-09-01', $filters[0]->value);
    }

    public function test_multi_day_date_period_uses_inclusive_between(): void
    {
        $filters = $this->factory()->create(
            DatasetKey::ACCOUNT_BALANCES,
            'snapshot_date',
            RelativeDatePreset::LAST_7_DAYS,
            $this->now(),
            $this->timezone(),
        );

        $this->assertCount(1, $filters);
        $this->assertSame(
            FilterOperator::BETWEEN,
            $filters[0]->operator,
        );
        $this->assertSame(
            ['2026-08-26', '2026-09-01'],
            $filters[0]->value,
        );
    }

    public function test_datetime_period_uses_dst_safe_utc_boundaries(): void
    {
        $filters = $this->factory()->create(
            DatasetKey::TRANSACTIONS,
            'booked_at',
            RelativeDatePreset::TODAY,
            new DateTimeImmutable(
                '2026-03-29T12:00:00+02:00',
            ),
            new DateTimeZone('Europe/Luxembourg'),
        );

        $this->assertCount(2, $filters);

        $this->assertSame(
            FilterOperator::ON_OR_AFTER,
            $filters[0]->operator,
        );
        $this->assertSame(
            '2026-03-28T23:00:00+00:00',
            $filters[0]->value,
        );

        $this->assertSame(
            FilterOperator::BEFORE,
            $filters[1]->operator,
        );
        $this->assertSame(
            '2026-03-29T22:00:00+00:00',
            $filters[1]->value,
        );
    }

    public function test_datetime_boundaries_compile_as_parameters(): void
    {
        $filters = $this->factory()->create(
            DatasetKey::TRANSACTIONS,
            'booked_at',
            RelativeDatePreset::TODAY,
            $this->now(),
            $this->timezone(),
        );

        $compiler = new FilterCompiler;
        $source = new TransactionDatasetSource;

        $lower = $compiler->compile($source, $filters[0]);
        $upper = $compiler->compile($source, $filters[1]);

        $this->assertSame(
            'transactions.booked_at >= ?',
            $lower->sql,
        );

        $this->assertSame(
            'transactions.booked_at < ?',
            $upper->sql,
        );
    }

    public function test_non_temporal_dimension_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Relative date filter requires a date or datetime dimension; [currency] is not temporal.',
        );

        $this->factory()->create(
            DatasetKey::TRANSACTIONS,
            'currency',
            RelativeDatePreset::TODAY,
            $this->now(),
            $this->timezone(),
        );
    }

    private function factory(): RelativeDateFilterFactory
    {
        $registry = new DatasetRegistry;

        return new RelativeDateFilterFactory(
            $registry,
            new FilterValidator(
                $registry,
                new DimensionFilterRules,
            ),
            new RelativeDateRangeResolver,
        );
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            '2026-08-31T23:30:00+00:00',
        );
    }

    private function timezone(): DateTimeZone
    {
        return new DateTimeZone('Europe/Luxembourg');
    }
}
