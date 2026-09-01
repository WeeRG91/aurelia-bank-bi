<?php

namespace App\Analytics\Filters;

use App\Analytics\Datasets\DimensionDefinition;
use App\Analytics\Datasets\DimensionKind;

final class DimensionFilterRules
{
    /**
     * @return list<FilterOperator>
     */
    public function allowedOperators(
        DimensionDefinition $dimension,
    ): array {
        $operators = match ($dimension->kind) {
            DimensionKind::IDENTIFIER,
            DimensionKind::CATEGORICAL,
            DimensionKind::GEOGRAPHIC => [
                FilterOperator::EQUALS,
                FilterOperator::NOT_EQUALS,
                FilterOperator::IN,
                FilterOperator::NOT_IN,
            ],

            DimensionKind::TEMPORAL => [
                FilterOperator::EQUALS,
                FilterOperator::NOT_EQUALS,
                FilterOperator::BEFORE,
                FilterOperator::AFTER,
                FilterOperator::BETWEEN,
            ],
        };

        if ($dimension->nullable) {
            $operators[] = FilterOperator::IS_NULL;
            $operators[] = FilterOperator::IS_NOT_NULL;
        }

        return $operators;
    }

    public function supports(
        DimensionDefinition $dimension,
        FilterOperator $operator,
    ): bool {
        return in_array(
            $operator,
            $this->allowedOperators($dimension),
            true,
        );
    }
}
