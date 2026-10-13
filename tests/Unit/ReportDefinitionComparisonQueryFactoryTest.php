<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Filters\FilterOperator;
use App\Analytics\Queries\ReportDefinitionComparisonQueryFactory;
use App\Analytics\Time\PeriodComparison;
use App\Analytics\Time\ReportingTimezone;
use DateTimeImmutable;
use InvalidArgumentException;
use Tests\TestCase;

final class ReportDefinitionComparisonQueryFactoryTest extends TestCase
{
    public function test_saved_definition_builds_previous_period_pair(): void
    {
        $pair = app(
            ReportDefinitionComparisonQueryFactory::class,
        )->create(
            dataset: DatasetKey::TRANSACTIONS,
            definition: [
                'dimensions' => ['currency'],
                'measures' => ['total_amount'],
                'filters' => [
                    [
                        'dimension' => 'transaction_type',
                        'operator' => 'equals',
                        'value' => 'transfer',
                    ],
                ],
                'relative_date' => [
                    'dimension' => 'booked_at',
                    'preset' => 'last_30_days',
                ],
                'limit' => 100,
            ],
            now: new DateTimeImmutable(
                '2026-10-13T10:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $this->assertSame(
            '2026-09-14',
            $pair->currentRange->startDate,
        );

        $this->assertSame(
            '2026-10-13',
            $pair->currentRange->endDate,
        );

        $this->assertSame(
            '2026-08-15',
            $pair->comparisonRange->startDate,
        );

        $this->assertSame(
            '2026-09-13',
            $pair->comparisonRange->endDate,
        );

        $this->assertSame(
            PeriodComparison::PREVIOUS_PERIOD,
            $pair->comparisonPeriod,
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
            FilterOperator::EQUALS,
            $pair->current->filters[0]->operator,
        );

        $this->assertSame(
            'transfer',
            $pair->current->filters[0]->value,
        );

        $this->assertCount(
            3,
            $pair->current->filters,
        );

        $this->assertCount(
            3,
            $pair->comparison->filters,
        );
    }

    public function test_comparison_requires_relative_date_definition(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'A comparison query requires a valid relative date definition.',
        );

        app(
            ReportDefinitionComparisonQueryFactory::class,
        )->create(
            dataset: DatasetKey::TRANSACTIONS,
            definition: [
                'dimensions' => [],
                'measures' => ['transaction_count'],
                'filters' => [],
                'relative_date' => null,
                'limit' => 100,
            ],
            now: new DateTimeImmutable(
                '2026-10-13T10:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );
    }
}
