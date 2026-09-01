<?php

namespace App\Analytics\Queries\Compilation;

use LogicException;

final readonly class CompiledFilter
{
    /**
     * @param  list<string|int|bool>  $bindings
     */
    public function __construct(
        public string $sql,
        public array $bindings,
    ) {
        if (trim($this->sql) === '') {
            throw new LogicException(
                'Compiled filter SQL must not be blank.',
            );
        }

        if (substr_count($this->sql, '?') !== count($this->bindings)) {
            throw new LogicException(
                'Compiled filter placeholders must match its bindings.',
            );
        }

        foreach ($this->bindings as $binding) {
            if (
                ! is_string($binding)
                && ! is_int($binding)
                && ! is_bool($binding)
            ) {
                throw new LogicException(
                    'Compiled filter bindings must be scalar values.',
                );
            }
        }
    }
}
