<?php

namespace App\Analytics\Datasets;

use InvalidArgumentException;

final readonly class DatasetDefinition
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public DatasetKey $key,
        public string $label,
        public string $description,
        public DatasetStatus $status,
    ) {
        if (trim($this->label) === '') {
            throw new InvalidArgumentException(
                'Dataset label must not be blank.',
            );
        }

        if (trim($this->description) === '') {
            throw new InvalidArgumentException(
                'Dataset description must not be blank.',
            );
        }
    }
}
