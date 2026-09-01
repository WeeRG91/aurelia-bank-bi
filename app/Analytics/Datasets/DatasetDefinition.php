<?php

namespace App\Analytics\Datasets;

use InvalidArgumentException;
use LogicException;

final readonly class DatasetDefinition
{
    /**
     * @var array<string, DimensionDefinition>
     */
    private array $dimensions;

    /**
     * @param  iterable<DimensionDefinition>  $dimensions
     */
    public function __construct(
        public DatasetKey $key,
        public string $label,
        public string $description,
        public DatasetStatus $status,
        iterable $dimensions = [],
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

        $indexedDimensions = [];

        foreach ($dimensions as $dimension) {
            if (isset($indexedDimensions[$dimension->key])) {
                throw new LogicException(
                    "Duplicate dimension [$dimension->key] in dataset [$this->key->value].",
                );
            }

            $indexedDimensions[$dimension->key] = $dimension;
        }

        $this->dimensions = $indexedDimensions;
    }

    /**
     * @return list<DimensionDefinition>
     */
    public function dimensions(): array
    {
        return array_values($this->dimensions);
    }

    public function findDimension(
        string $key,
    ): ?DimensionDefinition {
        return $this->dimensions[$key] ?? null;
    }

    public function dimension(
        string $key,
    ): DimensionDefinition {
        return $this->findDimension($key)
            ?? throw UnknownDimension::forDataset($this->key, $key);
    }
}
