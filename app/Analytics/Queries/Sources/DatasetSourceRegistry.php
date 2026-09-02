<?php

namespace App\Analytics\Queries\Sources;

use App\Analytics\Datasets\DatasetKey;
use LogicException;

final class DatasetSourceRegistry
{
    /**
     * @var array<string, DatasetSource>
     */
    private array $sources = [];

    public function __construct(?iterable $sources = null)
    {
        foreach (
            $sources ?? [
                new AccountBalanceDatasetSource,
                new TransactionDatasetSource,
            ] as $source
        ) {
            $key = $source->dataset()->value;

            if (isset($this->sources[$key])) {
                throw new LogicException(
                    "Duplicate query source for dataset [{$key}].",
                );
            }

            $this->sources[$key] = $source;
        }
    }

    public function find(
        DatasetKey|string $identifier,
    ): ?DatasetSource {
        $key = $identifier instanceof DatasetKey
            ? $identifier
            : DatasetKey::tryFrom($identifier);

        if ($key === null) {
            return null;
        }

        return $this->sources[$key->value] ?? null;
    }

    public function get(
        DatasetKey|string $identifier,
    ): DatasetSource {
        $source = $this->find($identifier);

        if ($source === null) {
            $value = $identifier instanceof DatasetKey
                ? $identifier->value
                : $identifier;

            throw new LogicException(
                "No query source is registered for dataset [{$value}].",
            );
        }

        return $source;
    }
}
