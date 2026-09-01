<?php

namespace App\Analytics\Datasets;

use InvalidArgumentException;

final readonly class DimensionDefinition
{
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public FieldDataType $dataType,
        public DimensionKind $kind,
        public SensitivityLevel $sensitivity,
        public bool $nullable,
    ) {
        if (preg_match('/^[a-z][a-z0-9_]*$/', $this->key) !== 1) {
            throw new InvalidArgumentException(
                "Invalid dimension key [$this->key].",
            );
        }

        if (trim($this->label) === '') {
            throw new InvalidArgumentException(
                'Dimension label must not be blank.',
            );
        }

        if (trim($this->description) === '') {
            throw new InvalidArgumentException(
                'Dimension description must not be blank.',
            );
        }

        if (
            $this->kind === DimensionKind::TEMPORAL
            && ! in_array(
                $this->dataType,
                [FieldDataType::DATE, FieldDataType::DATETIME],
                true,
            )
        ) {
            throw new InvalidArgumentException(
                'Temporal dimensions must use a date or datetime data type.',
            );
        }
    }
}
