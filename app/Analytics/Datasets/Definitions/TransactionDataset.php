<?php

namespace App\Analytics\Datasets\Definitions;

use App\Analytics\Datasets\AggregationFunction;
use App\Analytics\Datasets\DatasetDefinition;
use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetStatus;
use App\Analytics\Datasets\DimensionDefinition;
use App\Analytics\Datasets\DimensionKind;
use App\Analytics\Datasets\Dimensions\BankingDimensions;
use App\Analytics\Datasets\FieldDataType;
use App\Analytics\Datasets\MeasureDefinition;
use App\Analytics\Datasets\SensitivityLevel;

final class TransactionDataset
{
    public static function definition(): DatasetDefinition
    {
        return new DatasetDefinition(
            key: DatasetKey::TRANSACTIONS,
            label: 'Transactions',
            description: 'Account movements, transaction categories, directions, and statuses.',
            grain: 'One row per account transaction.',
            status: DatasetStatus::DRAFT,
            dimensions: [
                new DimensionDefinition(
                    key: 'transaction_reference',
                    label: 'Transaction Reference',
                    description: 'Stable business reference assigned to the transaction.',
                    dataType: FieldDataType::STRING,
                    kind: DimensionKind::IDENTIFIER,
                    sensitivity: SensitivityLevel::CONFIDENTIAL,
                    nullable: false,
                ),
                BankingDimensions::branch(),
                BankingDimensions::country(),
                BankingDimensions::accountType(),
                new DimensionDefinition(
                    key: 'transaction_type',
                    label: 'Transaction Type',
                    description: 'Business classification of the transaction.',
                    dataType: FieldDataType::STRING,
                    kind: DimensionKind::CATEGORICAL,
                    sensitivity: SensitivityLevel::INTERNAL,
                    nullable: false,
                ),
                new DimensionDefinition(
                    key: 'category',
                    label: 'Category',
                    description: 'Reporting category assigned to the transaction.',
                    dataType: FieldDataType::STRING,
                    kind: DimensionKind::CATEGORICAL,
                    sensitivity: SensitivityLevel::INTERNAL,
                    nullable: false,
                ),
                new DimensionDefinition(
                    key: 'currency',
                    label: 'Currency',
                    description: 'ISO 4217 currency code of the transaction.',
                    dataType: FieldDataType::STRING,
                    kind: DimensionKind::CATEGORICAL,
                    sensitivity: SensitivityLevel::INTERNAL,
                    nullable: false,
                ),
                new DimensionDefinition(
                    key: 'direction',
                    label: 'Direction',
                    description: 'Incoming or outgoing movement direction.',
                    dataType: FieldDataType::STRING,
                    kind: DimensionKind::CATEGORICAL,
                    sensitivity: SensitivityLevel::INTERNAL,
                    nullable: false,
                ),
                new DimensionDefinition(
                    key: 'status',
                    label: 'Status',
                    description: 'Current transaction processing status.',
                    dataType: FieldDataType::STRING,
                    kind: DimensionKind::CATEGORICAL,
                    sensitivity: SensitivityLevel::INTERNAL,
                    nullable: false,
                ),
                new DimensionDefinition(
                    key: 'booked_at',
                    label: 'Booked At',
                    description: 'Date and time when the transaction was booked.',
                    dataType: FieldDataType::DATETIME,
                    kind: DimensionKind::TEMPORAL,
                    sensitivity: SensitivityLevel::INTERNAL,
                    nullable: true,
                ),
                new DimensionDefinition(
                    key: 'booking_date',
                    label: 'Booking Date',
                    description: 'Local reporting date on which the transaction was booked.',
                    dataType: FieldDataType::DATE,
                    kind: DimensionKind::TEMPORAL,
                    sensitivity: SensitivityLevel::INTERNAL,
                    nullable: true,
                ),
                new DimensionDefinition(
                    key: 'booking_month',
                    label: 'Booking Month',
                    description: 'First calendar day of the local reporting month in which the transaction was booked.',
                    dataType: FieldDataType::DATE,
                    kind: DimensionKind::TEMPORAL,
                    sensitivity: SensitivityLevel::INTERNAL,
                    nullable: true,
                ),
                new DimensionDefinition(
                    key: 'booking_quarter',
                    label: 'Booking Quarter',
                    description: 'First calendar day of the local reporting quarter in which the transaction was booked.',
                    dataType: FieldDataType::DATE,
                    kind: DimensionKind::TEMPORAL,
                    sensitivity: SensitivityLevel::INTERNAL,
                    nullable: true,
                ),
                new DimensionDefinition(
                    key: 'booking_year',
                    label: 'Booking Year',
                    description: 'First calendar day of the local reporting year in which the transaction was booked.',
                    dataType: FieldDataType::DATE,
                    kind: DimensionKind::TEMPORAL,
                    sensitivity: SensitivityLevel::INTERNAL,
                    nullable: true,
                ),
                new DimensionDefinition(
                    key: 'value_date',
                    label: 'Value Date',
                    description: 'Banking date on which funds become effective.',
                    dataType: FieldDataType::DATE,
                    kind: DimensionKind::TEMPORAL,
                    sensitivity: SensitivityLevel::INTERNAL,
                    nullable: true,
                ),
            ],
            measures: [
                new MeasureDefinition(
                    key: 'transaction_count',
                    label: 'Transaction Count',
                    description: 'Number of account transactions in the result group.',
                    dataType: FieldDataType::INTEGER,
                    aggregation: AggregationFunction::COUNT,
                    sensitivity: SensitivityLevel::INTERNAL,
                ),
                new MeasureDefinition(
                    key: 'total_amount',
                    label: 'Total Amount',
                    description: 'Sum of transaction amounts in the result group.',
                    dataType: FieldDataType::DECIMAL,
                    aggregation: AggregationFunction::SUM,
                    sensitivity: SensitivityLevel::CONFIDENTIAL,
                    currencyDimension: 'currency',
                ),
                new MeasureDefinition(
                    key: 'incoming_amount',
                    label: 'Incoming Amount',
                    description: 'Sum of incoming transaction amounts in the result group.',
                    dataType: FieldDataType::DECIMAL,
                    aggregation: AggregationFunction::SUM,
                    sensitivity: SensitivityLevel::CONFIDENTIAL,
                    currencyDimension: 'currency',
                ),
                new MeasureDefinition(
                    key: 'outgoing_amount',
                    label: 'Outgoing Amount',
                    description: 'Sum of outgoing transaction amounts in the result group.',
                    dataType: FieldDataType::DECIMAL,
                    aggregation: AggregationFunction::SUM,
                    sensitivity: SensitivityLevel::CONFIDENTIAL,
                    currencyDimension: 'currency',
                ),
                new MeasureDefinition(
                    key: 'net_cash_flow',
                    label: 'Net Cash Flow',
                    description: 'Incoming amounts minus outgoing amounts for the included transaction rows.',
                    dataType: FieldDataType::DECIMAL,
                    aggregation: AggregationFunction::SUM,
                    sensitivity: SensitivityLevel::CONFIDENTIAL,
                    currencyDimension: 'currency',
                ),
            ],
        );
    }
}
