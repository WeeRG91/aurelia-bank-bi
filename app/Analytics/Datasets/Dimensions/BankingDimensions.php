<?php

namespace App\Analytics\Datasets\Dimensions;

use App\Analytics\Datasets\DimensionDefinition;
use App\Analytics\Datasets\DimensionKind;
use App\Analytics\Datasets\FieldDataType;
use App\Analytics\Datasets\SensitivityLevel;

final class BankingDimensions
{
    private function __construct() {}

    public static function branch(): DimensionDefinition
    {
        return new DimensionDefinition(
            key: 'branch',
            label: 'Branch',
            description: 'Bank branch responsible for the underlying banking record.',
            dataType: FieldDataType::STRING,
            kind: DimensionKind::CATEGORICAL,
            sensitivity: SensitivityLevel::INTERNAL,
            nullable: false,
        );
    }

    public static function country(): DimensionDefinition
    {
        return new DimensionDefinition(
            key: 'country',
            label: 'Country',
            description: 'Country in which the responsible branch operates.',
            dataType: FieldDataType::STRING,
            kind: DimensionKind::GEOGRAPHIC,
            sensitivity: SensitivityLevel::INTERNAL,
            nullable: false,
        );
    }

    public static function accountType(): DimensionDefinition
    {
        return new DimensionDefinition(
            key: 'account_type',
            label: 'Account Type',
            description: 'Business classification of the related banking account.',
            dataType: FieldDataType::STRING,
            kind: DimensionKind::CATEGORICAL,
            sensitivity: SensitivityLevel::INTERNAL,
            nullable: false,
        );
    }
}
