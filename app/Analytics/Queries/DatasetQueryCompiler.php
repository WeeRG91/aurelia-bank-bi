<?php

namespace App\Analytics\Queries;

use App\Analytics\Queries\Compilation\FilterCompiler;
use App\Analytics\Queries\Sources\DatasetSource;
use LogicException;

final readonly class DatasetQueryCompiler
{
    public function __construct(
        private FilterCompiler $filterCompiler,
    ) {}

    public function compile(
        DatasetSource $source,
        DatasetQuery $query,
    ): CompiledQuery {
        if ($source->dataset() !== $query->dataset) {
            throw new LogicException(
                "Query dataset [{$query->dataset->value}] does not match source dataset [{$source->dataset()->value}].",
            );
        }

        $select = array_map(
            fn (string $dimension): string => sprintf(
                '%s AS %s',
                $source->column($dimension),
                $dimension,
            ),
            $query->dimensions,
        );

        $sql = sprintf(
            'SELECT %s FROM %s',
            implode(', ', $select),
            $source->baseTable(),
        );

        foreach ($source->joins() as $join) {
            $sql .= sprintf(
                ' INNER JOIN %s ON %s = %s',
                $join->table,
                $join->leftColumn,
                $join->rightColumn,
            );
        }

        $bindings = [];
        $where = [];

        foreach ($query->filters as $filter) {
            $compiled = $this->filterCompiler->compile(
                $source,
                $filter,
            );

            $where[] = $compiled->sql;

            array_push(
                $bindings,
                ...$compiled->bindings,
            );
        }

        if ($where !== []) {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }

        $sql .= ' LIMIT ?';
        $bindings[] = $query->limit;

        return new CompiledQuery(
            sql: $sql,
            bindings: $bindings,
        );
    }
}
