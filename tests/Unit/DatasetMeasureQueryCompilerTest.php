<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Filters\DimensionFilterRules;
use App\Analytics\Filters\FilterCondition;
use App\Analytics\Filters\FilterValidator;
use App\Analytics\Queries\Authorization\DatasetRowScope;
use App\Analytics\Queries\Compilation\FilterCompiler;
use App\Analytics\Queries\CompiledQuery;
use App\Analytics\Queries\DatasetQuery;
use App\Analytics\Queries\DatasetQueryCompiler;
use App\Analytics\Queries\Sources\TransactionDatasetSource;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DatasetMeasureQueryCompilerTest extends TestCase
{
    public function test_count_measure_is_grouped_by_selected_dimensions(): void
    {
        $compiled = $this->compile(
            new DatasetQuery(
                dataset: DatasetKey::TRANSACTIONS,
                dimensions: ['branch', 'status'],
                measures: ['transaction_count'],
            ),
            DatasetRowScope::branch(42),
        );

        $this->assertStringContainsString(
            'COUNT(transactions.id) AS transaction_count',
            $compiled->sql,
        );

        $this->assertStringContainsString(
            'GROUP BY branches.branch_code, transactions.status',
            $compiled->sql,
        );

        $this->assertSame([42, 100], $compiled->bindings);
    }

    public function test_total_amount_can_be_grouped_by_currency(): void
    {
        $compiled = $this->compile(
            new DatasetQuery(
                dataset: DatasetKey::TRANSACTIONS,
                dimensions: ['currency'],
                measures: ['total_amount'],
            ),
        );

        $this->assertStringContainsString(
            'SUM(transactions.amount) AS total_amount',
            $compiled->sql,
        );

        $this->assertStringContainsString(
            'GROUP BY transactions.currency',
            $compiled->sql,
        );
    }

    public function test_total_amount_can_use_a_single_currency_filter(): void
    {
        $compiled = $this->compile(
            new DatasetQuery(
                dataset: DatasetKey::TRANSACTIONS,
                dimensions: ['branch'],
                measures: ['total_amount'],
                filters: [
                    $this->filter(
                        'currency',
                        'equals',
                        'EUR',
                    ),
                ],
            ),
        );

        $this->assertStringContainsString(
            'WHERE transactions.currency = ?',
            $compiled->sql,
        );

        $this->assertSame(['EUR', 100], $compiled->bindings);
    }

    public function test_cross_currency_total_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Currency-aware measure [total_amount] requires dimension [currency] or a single-currency filter.',
        );

        $this->compile(
            new DatasetQuery(
                dataset: DatasetKey::TRANSACTIONS,
                dimensions: ['branch'],
                measures: ['total_amount'],
            ),
        );
    }

    public function test_count_measure_can_produce_a_grand_total(): void
    {
        $compiled = $this->compile(
            new DatasetQuery(
                dataset: DatasetKey::TRANSACTIONS,
                measures: ['transaction_count'],
            ),
        );

        $this->assertStringContainsString(
            'SELECT COUNT(transactions.id) AS transaction_count',
            $compiled->sql,
        );

        $this->assertStringNotContainsString(
            ' GROUP BY ',
            $compiled->sql,
        );
    }

    private function compile(
        DatasetQuery $query,
        ?DatasetRowScope $scope = null,
    ): CompiledQuery {
        return (new DatasetQueryCompiler(
            new FilterCompiler,
            new DatasetRegistry,
        ))->compile(
            new TransactionDatasetSource,
            $query,
            $scope ?? DatasetRowScope::unrestricted(),
        );
    }

    private function filter(
        string $dimension,
        string $operator,
        mixed $value = null,
    ): FilterCondition {
        return (new FilterValidator(
            new DatasetRegistry,
            new DimensionFilterRules,
        ))->validate(
            DatasetKey::TRANSACTIONS,
            $dimension,
            $operator,
            $value,
        );
    }
}
