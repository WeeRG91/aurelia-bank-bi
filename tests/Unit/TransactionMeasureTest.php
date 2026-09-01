<?php

namespace Tests\Unit;

use App\Analytics\Datasets\AggregationFunction;
use App\Analytics\Datasets\DatasetDefinition;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\DatasetStatus;
use App\Analytics\Datasets\FieldDataType;
use App\Analytics\Datasets\MeasureDefinition;
use App\Analytics\Datasets\SensitivityLevel;
use App\Analytics\Datasets\UnknownMeasure;
use LogicException;
use PHPUnit\Framework\TestCase;

class TransactionMeasureTest extends TestCase
{
    public function test_transaction_measures_have_stable_keys(): void
    {
        $dataset = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS);

        $this->assertSame(
            [
                'transaction_count',
                'total_amount',
                'incoming_amount',
                'outgoing_amount',
                'net_cash_flow',
            ],
            array_map(
                fn (MeasureDefinition $measure): string => $measure->key,
                $dataset->measures(),
            ),
        );
    }

    public function test_transaction_count_has_count_semantics(): void
    {
        $measure = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS)
            ->measure('transaction_count');

        $this->assertSame(
            AggregationFunction::COUNT,
            $measure->aggregation,
        );

        $this->assertSame(
            FieldDataType::INTEGER,
            $measure->dataType,
        );

        $this->assertSame(
            SensitivityLevel::INTERNAL,
            $measure->sensitivity,
        );
    }

    public function test_total_amount_is_currency_aware(): void
    {
        $measure = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS)
            ->measure('total_amount');

        $this->assertSame(
            AggregationFunction::SUM,
            $measure->aggregation,
        );

        $this->assertSame(
            FieldDataType::DECIMAL,
            $measure->dataType,
        );

        $this->assertSame(
            SensitivityLevel::CONFIDENTIAL,
            $measure->sensitivity,
        );

        $this->assertSame(
            'currency',
            $measure->currencyDimension,
        );
    }

    public function test_unknown_measure_is_rejected(): void
    {
        $dataset = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS);

        $this->expectException(UnknownMeasure::class);
        $this->expectExceptionMessage(
            'Unknown measure [password_count] for dataset [transactions].',
        );

        $dataset->measure('password_count');
    }

    public function test_currency_measure_requires_its_dimension_in_the_dataset(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Currency dimension [currency] for measure [total_amount] does not exist in dataset [transactions].',
        );

        new DatasetDefinition(
            key: DatasetKey::TRANSACTIONS,
            label: 'Transactions',
            description: 'Transactions.',
            grain: 'One row per transaction.',
            status: DatasetStatus::DRAFT,
            measures: [
                new MeasureDefinition(
                    key: 'total_amount',
                    label: 'Total Amount',
                    description: 'Total amount.',
                    dataType: FieldDataType::DECIMAL,
                    aggregation: AggregationFunction::SUM,
                    sensitivity: SensitivityLevel::CONFIDENTIAL,
                    currencyDimension: 'currency',
                ),
            ],
        );
    }

    public function test_directional_amount_measures_are_currency_aware_sums(): void
    {
        $dataset = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS);

        foreach ([
            'incoming_amount',
            'outgoing_amount',
            'net_cash_flow',
        ] as $measureKey) {
            $measure = $dataset->measure($measureKey);

            $this->assertSame(
                AggregationFunction::SUM,
                $measure->aggregation,
            );

            $this->assertSame(
                FieldDataType::DECIMAL,
                $measure->dataType,
            );

            $this->assertSame(
                SensitivityLevel::CONFIDENTIAL,
                $measure->sensitivity,
            );

            $this->assertSame(
                'currency',
                $measure->currencyDimension,
            );
        }
    }
}
