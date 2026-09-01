<?php

namespace App\Analytics\Datasets;

use InvalidArgumentException;

final readonly class MeasureDefinition
{
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public FieldDataType $dataType,
        public AggregationFunction $aggregation,
        public SensitivityLevel $sensitivity,
        public ?string $currencyDimension = null,
    ) {
        if (preg_match('/^[a-z][a-z0-9_]*$/', $this->key) !== 1) {
            throw new InvalidArgumentException(
                "Invalid measure key [{$this->key}].",
            );
        }

        if (trim($this->label) === '') {
            throw new InvalidArgumentException(
                'Measure label must not be blank.',
            );
        }

        if (trim($this->description) === '') {
            throw new InvalidArgumentException(
                'Measure description must not be blank.',
            );
        }

        if (
            ! in_array(
                $this->dataType,
                [
                    FieldDataType::INTEGER,
                    FieldDataType::DECIMAL,
                ],
                true,
            )
        ) {
            throw new InvalidArgumentException(
                'Measures must use an integer or decimal data type.',
            );
        }

        if (
            $this->aggregation === AggregationFunction::COUNT
            && $this->dataType !== FieldDataType::INTEGER
        ) {
            throw new InvalidArgumentException(
                'Count measures must use the integer data type.',
            );
        }

        if (
            $this->currencyDimension !== null
            && preg_match(
                '/^[a-z][a-z0-9_]*$/',
                $this->currencyDimension,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'Currency dimension must use a safe semantic key.',
            );
        }

        if (
            $this->currencyDimension !== null
            && $this->dataType !== FieldDataType::DECIMAL
        ) {
            throw new InvalidArgumentException(
                'Currency-aware measures must use the decimal data type.',
            );
        }
    }
}
