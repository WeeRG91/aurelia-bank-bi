<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\FieldDataType;
use App\Analytics\Exports\ExportSchemaFactory;
use App\Analytics\Queries\DatasetQuery;
use PHPUnit\Framework\TestCase;

class ExportSchemaFactoryTest extends TestCase
{
    public function test_it_builds_columns_in_query_selection_order(): void
    {
        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: [
                'booking_date',
                'currency',
            ],
            measures: [
                'transaction_count',
                'total_amount',
            ],
        );

        $columns = (new ExportSchemaFactory(
            new DatasetRegistry,
        ))->forQuery($query);

        $this->assertSame(
            [
                'booking_date',
                'currency',
                'transaction_count',
                'total_amount',
            ],
            array_column($columns, 'key'),
        );

        $this->assertSame(
            [
                'Booking Date',
                'Currency',
                'Transaction Count',
                'Total Amount',
            ],
            array_column($columns, 'label'),
        );

        $this->assertSame(
            [
                FieldDataType::DATE,
                FieldDataType::STRING,
                FieldDataType::INTEGER,
                FieldDataType::DECIMAL,
            ],
            array_column($columns, 'dataType'),
        );
    }
}
