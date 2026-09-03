<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Filters\FilterOperator;
use App\Analytics\Queries\ReportDefinitionQueryFactory;
use App\Analytics\Time\ReportingTimezone;
use DateTimeImmutable;
use Tests\TestCase;

class ReportDefinitionQueryFactoryTest extends TestCase
{
    public function test_it_rebuilds_a_saved_definition_as_a_dataset_query(): void
    {
        $query = app(ReportDefinitionQueryFactory::class)->create(
            dataset: DatasetKey::TRANSACTIONS,
            definition: [
                'dimensions' => [
                    'booking_date',
                    'currency',
                ],
                'measures' => [
                    'total_amount',
                ],
                'filters' => [
                    [
                        'dimension' => 'currency',
                        'operator' => 'equals',
                        'value' => ' EUR ',
                    ],
                ],
                'relative_date' => [
                    'dimension' => 'booked_at',
                    'preset' => 'previous_month',
                ],
                'limit' => 250,
            ],
            now: new DateTimeImmutable(
                '2026-09-03T12:00:00+02:00',
            ),
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $this->assertSame(
            DatasetKey::TRANSACTIONS,
            $query->dataset,
        );
        $this->assertSame(
            ['booking_date', 'currency'],
            $query->dimensions,
        );
        $this->assertSame(
            ['total_amount'],
            $query->measures,
        );
        $this->assertSame(250, $query->limit);
        $this->assertCount(3, $query->filters);

        $this->assertSame(
            FilterOperator::EQUALS,
            $query->filters[0]->operator,
        );
        $this->assertSame('EUR', $query->filters[0]->value);

        $this->assertSame(
            '2026-07-31T22:00:00+00:00',
            $query->filters[1]->value,
        );
        $this->assertSame(
            '2026-08-31T22:00:00+00:00',
            $query->filters[2]->value,
        );
    }
}
