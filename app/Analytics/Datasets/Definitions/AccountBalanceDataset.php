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

final class AccountBalanceDataset
{
    public static function definition(): DatasetDefinition
    {
        return new DatasetDefinition(
            key: DatasetKey::ACCOUNT_BALANCES,
            label: 'Account Balances',
            description: 'Point-in-time ledger and available balances for banking accounts.',
            grain: 'One row per account per snapshot date.',
            status: DatasetStatus::DRAFT,
            dimensions: [
                new DimensionDefinition(
                    key: 'account_number',
                    label: 'Account Number',
                    description: 'Stable business identifier of the banking account.',
                    dataType: FieldDataType::STRING,
                    kind: DimensionKind::IDENTIFIER,
                    sensitivity: SensitivityLevel::CONFIDENTIAL,
                    nullable: false,
                ),
                BankingDimensions::branch(),
                BankingDimensions::country(),
                BankingDimensions::accountType(),
                new DimensionDefinition(
                    key: 'currency',
                    label: 'Account Currency',
                    description: 'ISO 4217 currency in which the account is maintained.',
                    dataType: FieldDataType::STRING,
                    kind: DimensionKind::CATEGORICAL,
                    sensitivity: SensitivityLevel::INTERNAL,
                    nullable: false,
                ),
                new DimensionDefinition(
                    key: 'account_status',
                    label: 'Account Status',
                    description: 'Lifecycle status of the banking account.',
                    dataType: FieldDataType::STRING,
                    kind: DimensionKind::CATEGORICAL,
                    sensitivity: SensitivityLevel::INTERNAL,
                    nullable: false,
                ),
                new DimensionDefinition(
                    key: 'snapshot_date',
                    label: 'Snapshot Date',
                    description: 'Date on which the account balances were recorded.',
                    dataType: FieldDataType::DATE,
                    kind: DimensionKind::TEMPORAL,
                    sensitivity: SensitivityLevel::INTERNAL,
                    nullable: false,
                ),
            ],
            measures: [
                new MeasureDefinition(
                    key: 'snapshot_count',
                    label: 'Snapshot Count',
                    description: 'Number of account balance snapshot rows in the result group.',
                    dataType: FieldDataType::INTEGER,
                    aggregation: AggregationFunction::COUNT,
                    sensitivity: SensitivityLevel::INTERNAL,
                ),
                new MeasureDefinition(
                    key: 'total_ledger_balance',
                    label: 'Total Ledger Balance',
                    description: 'Sum of ledger balances at a specific snapshot date.',
                    dataType: FieldDataType::DECIMAL,
                    aggregation: AggregationFunction::SUM,
                    sensitivity: SensitivityLevel::CONFIDENTIAL,
                    currencyDimension: 'currency',
                    requiredDimensions: ['snapshot_date'],
                ),
                new MeasureDefinition(
                    key: 'total_available_balance',
                    label: 'Total Available Balance',
                    description: 'Sum of available balances at a specific snapshot date.',
                    dataType: FieldDataType::DECIMAL,
                    aggregation: AggregationFunction::SUM,
                    sensitivity: SensitivityLevel::CONFIDENTIAL,
                    currencyDimension: 'currency',
                    requiredDimensions: ['snapshot_date'],
                ),
                new MeasureDefinition(
                    key: 'average_ledger_balance',
                    label: 'Average Ledger Balance',
                    description: 'Average ledger balance per account at a specific snapshot date.',
                    dataType: FieldDataType::DECIMAL,
                    aggregation: AggregationFunction::AVERAGE,
                    sensitivity: SensitivityLevel::CONFIDENTIAL,
                    currencyDimension: 'currency',
                    requiredDimensions: ['snapshot_date'],
                ),
                new MeasureDefinition(
                    key: 'average_available_balance',
                    label: 'Average Available Balance',
                    description: 'Average available balance per account at a specific snapshot date.',
                    dataType: FieldDataType::DECIMAL,
                    aggregation: AggregationFunction::AVERAGE,
                    sensitivity: SensitivityLevel::CONFIDENTIAL,
                    currencyDimension: 'currency',
                    requiredDimensions: ['snapshot_date'],
                ),
            ],
        );
    }
}
