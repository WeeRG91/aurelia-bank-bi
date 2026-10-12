<?php

namespace App\Analytics\Warehouse\Dimensions;

use InvalidArgumentException;
use JsonException;

final readonly class DimensionAttributeHasher
{
    /**
     * @param  array<string, bool|int|string|null>  $attributes
     *
     * @throws JsonException
     */
    public function hash(array $attributes): string
    {
        if ($attributes === []) {
            throw new InvalidArgumentException(
                'Dimension attributes cannot be empty.',
            );
        }

        foreach ($attributes as $name => $value) {
            if (! is_string($name)) {
                throw new InvalidArgumentException(
                    'Dimension attribute names must be strings.',
                );
            }

            if (
                $value !== null &&
                ! is_bool($value) &&
                ! is_int($value) &&
                ! is_string($value)
            ) {
                throw new InvalidArgumentException(
                    "Dimension attribute [{$name}] must be a scalar canonical value or null.",
                );
            }
        }

        ksort($attributes, SORT_STRING);

        $canonical = json_encode(
            $attributes,
            JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE,
        );

        return hash('sha256', $canonical);
    }
}
