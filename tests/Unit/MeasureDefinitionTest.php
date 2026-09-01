<?php

namespace Tests\Unit;

use App\Analytics\Datasets\AggregationFunction;
use App\Analytics\Datasets\FieldDataType;
use App\Analytics\Datasets\MeasureDefinition;
use App\Analytics\Datasets\SensitivityLevel;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MeasureDefinitionTest extends TestCase
{
    public function test_aggregation_functions_have_stable_values(): void
    {
        $this->assertSame(
            [
                'count',
                'sum',
                'average',
                'minimum',
                'maximum',
            ],
            array_column(
                AggregationFunction::cases(),
                'value',
            ),
        );
    }

    public function test_count_measure_is_defined_as_an_integer(): void
    {
        $measure = new MeasureDefinition(
            key: 'transaction_count',
            label: 'Transaction Count',
            description: 'Number of transactions.',
            dataType: FieldDataType::INTEGER,
            aggregation: AggregationFunction::COUNT,
            sensitivity: SensitivityLevel::INTERNAL,
        );

        $this->assertSame(
            AggregationFunction::COUNT,
            $measure->aggregation,
        );

        $this->assertSame(
            FieldDataType::INTEGER,
            $measure->dataType,
        );

        $this->assertNull($measure->currencyDimension);
    }

    public function test_monetary_measure_identifies_its_currency_dimension(): void
    {
        $measure = new MeasureDefinition(
            key: 'total_amount',
            label: 'Total Amount',
            description: 'Sum of transaction amounts.',
            dataType: FieldDataType::DECIMAL,
            aggregation: AggregationFunction::SUM,
            sensitivity: SensitivityLevel::CONFIDENTIAL,
            currencyDimension: 'currency',
        );

        $this->assertSame(
            'currency',
            $measure->currencyDimension,
        );
    }

    public function test_measure_key_must_be_safe(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid measure key [sum(amount)].',
        );

        new MeasureDefinition(
            key: 'sum(amount)',
            label: 'Unsafe',
            description: 'Unsafe measure.',
            dataType: FieldDataType::DECIMAL,
            aggregation: AggregationFunction::SUM,
            sensitivity: SensitivityLevel::INTERNAL,
        );
    }

    public function test_measure_must_be_numeric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Measures must use an integer or decimal data type.',
        );

        new MeasureDefinition(
            key: 'invalid_measure',
            label: 'Invalid Measure',
            description: 'Invalid non-numeric measure.',
            dataType: FieldDataType::STRING,
            aggregation: AggregationFunction::COUNT,
            sensitivity: SensitivityLevel::INTERNAL,
        );
    }

    public function test_count_measure_cannot_use_decimal_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Count measures must use the integer data type.',
        );

        new MeasureDefinition(
            key: 'invalid_count',
            label: 'Invalid Count',
            description: 'Invalid decimal count.',
            dataType: FieldDataType::DECIMAL,
            aggregation: AggregationFunction::COUNT,
            sensitivity: SensitivityLevel::INTERNAL,
        );
    }

    public function test_currency_aware_measure_must_be_decimal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Currency-aware measures must use the decimal data type.',
        );

        new MeasureDefinition(
            key: 'invalid_money',
            label: 'Invalid Money',
            description: 'Invalid monetary measure.',
            dataType: FieldDataType::INTEGER,
            aggregation: AggregationFunction::SUM,
            sensitivity: SensitivityLevel::CONFIDENTIAL,
            currencyDimension: 'currency',
        );
    }
}
