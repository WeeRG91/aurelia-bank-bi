<?php

namespace App\Analytics\Queries;

use LogicException;

final readonly class CompiledQuery
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
                'Compiled query SQL must not be blank.',
            );
        }

        if (substr_count($this->sql, '?') !== count($this->bindings)) {
            throw new LogicException(
                'Compiled query placeholders must match its bindings.',
            );
        }
    }
}
