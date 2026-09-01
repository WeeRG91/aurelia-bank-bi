<?php

namespace App\Analytics\Queries;

use App\Analytics\Datasets\AggregationFunction;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\MeasureDefinition;
use App\Analytics\Filters\FilterOperator;
use App\Analytics\Queries\Authorization\DatasetRowScope;
use App\Analytics\Queries\Authorization\RowScopeType;
use App\Analytics\Queries\Compilation\CompiledFilter;
use App\Analytics\Queries\Compilation\FilterCompiler;
use App\Analytics\Queries\Sources\DatasetSource;
use InvalidArgumentException;
use LogicException;

final readonly class DatasetQueryCompiler
{
    public function __construct(
        private FilterCompiler $filterCompiler,
        private DatasetRegistry $registry,
    ) {}

    public function compile(
        DatasetSource $source,
        DatasetQuery $query,
        DatasetRowScope $scope,
    ): CompiledQuery {
        if ($source->dataset() !== $query->dataset) {
            throw new LogicException(
                "Query dataset [{$query->dataset->value}] does not match source dataset [{$source->dataset()->value}].",
            );
        }

        $definition = $this->registry->get($query->dataset);

        $select = array_map(
            fn (string $dimension): string => sprintf(
                '%s AS %s',
                $source->column($dimension),
                $definition->dimension($dimension)->key,
            ),
            $query->dimensions,
        );

        foreach ($query->measures as $measureKey) {
            $measure = $definition->measure($measureKey);

            $this->assertCurrencySafety(
                $query,
                $measure,
            );

            $select[] = $this->compileMeasure(
                $source,
                $measure,
            );
        }

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

        $compiledScope = $this->compileScope(
            $source,
            $scope,
        );

        if ($compiledScope !== null) {
            $where[] = $compiledScope->sql;

            array_push(
                $bindings,
                ...$compiledScope->bindings,
            );
        }

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

        if (
            $query->measures !== []
            && $query->dimensions !== []
        ) {
            $groupBy = array_map(
                fn (string $dimension): string => $source->column(
                    $dimension,
                ),
                $query->dimensions,
            );

            $sql .= ' GROUP BY '.implode(', ', $groupBy);
        }

        $sql .= ' LIMIT ?';
        $bindings[] = $query->limit;

        return new CompiledQuery(
            sql: $sql,
            bindings: $bindings,
        );
    }

    private function compileMeasure(
        DatasetSource $source,
        MeasureDefinition $measure,
    ): string {
        $function = match ($measure->aggregation) {
            AggregationFunction::COUNT => 'COUNT',
            AggregationFunction::SUM => 'SUM',
            AggregationFunction::AVERAGE => 'AVG',
            AggregationFunction::MINIMUM => 'MIN',
            AggregationFunction::MAXIMUM => 'MAX',
        };

        return sprintf(
            '%s(%s) AS %s',
            $function,
            $source->measureColumn($measure->key),
            $measure->key,
        );
    }

    private function assertCurrencySafety(
        DatasetQuery $query,
        MeasureDefinition $measure,
    ): void {
        $currencyDimension = $measure->currencyDimension;

        if ($currencyDimension === null) {
            return;
        }

        if (
            in_array(
                $currencyDimension,
                $query->dimensions,
                true,
            )
        ) {
            return;
        }

        foreach ($query->filters as $filter) {
            if ($filter->dimension !== $currencyDimension) {
                continue;
            }

            if (
                $filter->operator === FilterOperator::EQUALS
                && ! is_array($filter->value)
                && $filter->value !== null
            ) {
                return;
            }

            if (
                $filter->operator === FilterOperator::IN
                && is_array($filter->value)
                && count($filter->value) === 1
            ) {
                return;
            }
        }

        throw new InvalidArgumentException(
            "Currency-aware measure [{$measure->key}] requires dimension [{$currencyDimension}] or a single-currency filter.",
        );
    }

    private function compileScope(
        DatasetSource $source,
        DatasetRowScope $scope,
    ): ?CompiledFilter {
        return match ($scope->type) {
            RowScopeType::UNRESTRICTED => null,

            RowScopeType::DENIED => new CompiledFilter(
                sql: '1 = 0',
                bindings: [],
            ),

            RowScopeType::BRANCH => new CompiledFilter(
                sql: $source->branchScopeColumn().' = ?',
                bindings: [
                    $scope->branchId
                    ?? throw new LogicException(
                        'Branch row scope is missing its branch identifier.',
                    ),
                ],
            ),
        };
    }
}
