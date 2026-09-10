<?php

namespace App\Analytics\Auditing;

use InvalidArgumentException;

final readonly class AuditContext
{
    private const array ALLOWED_KEYS = [
        'definition_version',
        'dimension_count',
        'duration_ms',
        'file_size_bytes',
        'filter_count',
        'format',
        'frequency',
        'limit',
        'measure_count',
        'period',
        'reason_code',
        'row_count',
        'status_from',
        'status_to',
        'widget_count',
    ];

    /**
     * @var array<string, bool|int|string|null>
     */
    private array $values;

    /**
     * @param  array<string, mixed>  $values
     */
    private function __construct(array $values)
    {
        $unknownKeys = array_diff(
            array_keys($values),
            self::ALLOWED_KEYS,
        );

        if ($unknownKeys !== []) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported audit context key [%s].',
                implode(', ', $unknownKeys),
            ));
        }

        foreach ($values as $key => $value) {
            if (
                $value !== null
                && ! is_bool($value)
                && ! is_int($value)
                && ! is_string($value)
            ) {
                throw new InvalidArgumentException(
                    "Audit context value [{$key}] must be scalar or null.",
                );
            }
        }

        ksort($values);

        /** @var array<string, bool|int|string|null> $values */
        $this->values = $values;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function from(array $values = []): self
    {
        return new self($values);
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    public function toArray(): array
    {
        return $this->values;
    }
}
