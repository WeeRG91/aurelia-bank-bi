<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\DimensionDefinition;
use App\Analytics\Datasets\DimensionKind;
use App\Analytics\Datasets\FieldDataType;
use App\Analytics\Datasets\SensitivityLevel;
use App\Analytics\Datasets\UnknownDimension;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TransactionDatasetTest extends TestCase
{
    public function test_transaction_dimensions_have_stable_keys(): void
    {
        $dataset = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS);

        $this->assertSame(
            [
                'transaction_reference',
                'transaction_type',
                'category',
                'currency',
                'direction',
                'status',
                'booked_at',
                'value_date',
            ],
            array_map(
                fn (DimensionDefinition $dimension): string => $dimension->key,
                $dataset->dimensions(),
            ),
        );
    }

    public function test_transaction_reference_is_a_confidential_identifier(): void
    {
        $dimension = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS)
            ->dimension('transaction_reference');

        $this->assertSame(
            DimensionKind::IDENTIFIER,
            $dimension->kind,
        );

        $this->assertSame(
            FieldDataType::STRING,
            $dimension->dataType,
        );

        $this->assertSame(
            SensitivityLevel::CONFIDENTIAL,
            $dimension->sensitivity,
        );

        $this->assertFalse($dimension->nullable);
    }

    public function test_transaction_temporal_dimensions_preserve_their_types(): void
    {
        $dataset = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS);

        $this->assertSame(
            FieldDataType::DATETIME,
            $dataset->dimension('booked_at')->dataType,
        );

        $this->assertSame(
            FieldDataType::DATE,
            $dataset->dimension('value_date')->dataType,
        );

        $this->assertTrue(
            $dataset->dimension('booked_at')->nullable,
        );

        $this->assertTrue(
            $dataset->dimension('value_date')->nullable,
        );
    }

    public function test_unknown_dimension_is_rejected(): void
    {
        $dataset = (new DatasetRegistry)
            ->get(DatasetKey::TRANSACTIONS);

        $this->expectException(UnknownDimension::class);
        $this->expectExceptionMessage(
            'Unknown dimension [password] for dataset [transactions].',
        );

        $dataset->dimension('password');
    }

    public function test_temporal_dimension_requires_a_temporal_data_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Temporal dimensions must use a date or datetime data type.',
        );

        new DimensionDefinition(
            key: 'invalid_time',
            label: 'Invalid Time',
            description: 'Invalid temporal metadata.',
            dataType: FieldDataType::STRING,
            kind: DimensionKind::TEMPORAL,
            sensitivity: SensitivityLevel::INTERNAL,
            nullable: false,
        );
    }
}
