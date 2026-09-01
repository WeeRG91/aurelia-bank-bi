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
     * @var array<string, MeasureDefinition>
     */
    private array $measures;

    /**
     * @param  iterable<DimensionDefinition>  $dimensions
     * @param  iterable<MeasureDefinition>  $measures
     */
    public function __construct(
        public DatasetKey $key,
        public string $label,
        public string $description,
        public string $grain,
        public DatasetStatus $status,
        iterable $dimensions = [],
        iterable $measures = [],
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

        if (trim($this->grain) === '') {
            throw new InvalidArgumentException(
                'Dataset grain must not be blank.',
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

        $indexedMeasures = [];

        foreach ($measures as $measure) {
            if (isset($indexedMeasures[$measure->key])) {
                throw new LogicException(
                    "Duplicate measure [{$measure->key}] in dataset [{$this->key->value}].",
                );
            }

            if (isset($indexedDimensions[$measure->key])) {
                throw new LogicException(
                    "Semantic key [{$measure->key}] cannot be both a dimension and a measure in dataset [{$this->key->value}].",
                );
            }

            if (
                $measure->currencyDimension !== null
                && ! isset(
                    $indexedDimensions[$measure->currencyDimension],
                )
            ) {
                throw new LogicException(
                    "Currency dimension [{$measure->currencyDimension}] for measure [{$measure->key}] does not exist in dataset [{$this->key->value}].",
                );
            }

            $indexedMeasures[$measure->key] = $measure;
        }

        $this->dimensions = $indexedDimensions;
        $this->measures = $indexedMeasures;
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

    /**
     * @return list<MeasureDefinition>
     */
    public function measures(): array
    {
        return array_values($this->measures);
    }

    public function findMeasure(
        string $key,
    ): ?MeasureDefinition {
        return $this->measures[$key] ?? null;
    }

    public function measure(
        string $key,
    ): MeasureDefinition {
        return $this->findMeasure($key)
            ?? throw UnknownMeasure::forDataset($this->key, $key);
    }
}
