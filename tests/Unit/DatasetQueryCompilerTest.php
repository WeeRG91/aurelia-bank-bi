<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetKey;
use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Datasets\UnknownDimension;
use App\Analytics\Filters\DimensionFilterRules;
use App\Analytics\Filters\FilterCondition;
use App\Analytics\Filters\FilterValidator;
use App\Analytics\Queries\Authorization\DatasetRowScope;
use App\Analytics\Queries\Compilation\FilterCompiler;
use App\Analytics\Queries\DatasetQuery;
use App\Analytics\Queries\DatasetQueryCompiler;
use App\Analytics\Queries\Sources\DatasetSource;
use App\Analytics\Queries\Sources\DimensionSource;
use App\Analytics\Queries\Sources\JoinDefinition;
use App\Analytics\Queries\Sources\JoinType;
use App\Analytics\Queries\Sources\MeasureSource;
use App\Analytics\Queries\Sources\TransactionDatasetSource;
use App\Analytics\Queries\Sources\WarehouseTransactionDatasetSource;
use App\Analytics\Time\ReportingTimezone;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

class DatasetQueryCompilerTest extends TestCase
{
    public function test_transaction_query_is_compiled_with_separate_bindings(): void
    {
        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: [
                'transaction_reference',
                'branch',
                'currency',
            ],
            filters: [
                $this->filter('currency', 'equals', 'EUR'),
                $this->filter(
                    'status',
                    'in',
                    ['booked', 'reversed'],
                ),
            ],
            limit: 100,
        );

        $compiled = $this->compiler()->compile(
            new TransactionDatasetSource,
            $query,
            DatasetRowScope::branch(42),
        );

        $this->assertSame(
            'SELECT transactions.transaction_reference AS transaction_reference, branches.branch_code AS branch, transactions.currency AS currency FROM transactions INNER JOIN accounts ON transactions.account_id = accounts.id INNER JOIN branches ON accounts.branch_id = branches.id WHERE branches.id = ? AND transactions.currency = ? AND transactions.status IN (?, ?) LIMIT ?',
            $compiled->sql,
        );

        $this->assertSame(
            [42, 'EUR', 'booked', 'reversed', 100],
            $compiled->bindings,
        );
    }

    public function test_query_without_filters_has_no_where_clause(): void
    {
        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['transaction_reference'],
            limit: 25,
        );

        $compiled = $this->compiler()->compile(
            new TransactionDatasetSource,
            $query,
            DatasetRowScope::unrestricted(),
        );

        $this->assertStringNotContainsString(
            ' WHERE ',
            $compiled->sql,
        );

        $this->assertSame([25], $compiled->bindings);
    }

    public function test_unknown_selected_dimension_is_rejected(): void
    {
        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['password'],
        );

        $this->expectException(UnknownDimension::class);

        $this->compiler()->compile(
            new TransactionDatasetSource,
            $query,
            DatasetRowScope::unrestricted(),
        );
    }

    public function test_query_rejects_filters_from_another_dataset(): void
    {
        $foreignFilter = $this->validator()->validate(
            'account_balances',
            'currency',
            'equals',
            'EUR',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Filter dataset [account_balances] does not match query dataset [transactions].',
        );

        new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['currency'],
            filters: [$foreignFilter],
        );
    }

    public function test_query_rejects_duplicate_dimensions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Dataset query dimensions must be unique.',
        );

        new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['currency', 'currency'],
        );
    }

    public function test_query_limit_is_bounded(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Dataset query limit must be between 1 and 500.',
        );

        new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['currency'],
            limit: 501,
        );
    }

    private function filter(
        string $dimension,
        string $operator,
        mixed $value = null,
    ): FilterCondition {
        return $this->validator()->validate(
            'transactions',
            $dimension,
            $operator,
            $value,
        );
    }

    private function validator(): FilterValidator
    {
        return new FilterValidator(
            new DatasetRegistry,
            new DimensionFilterRules,
        );
    }

    private function compiler(): DatasetQueryCompiler
    {
        return new DatasetQueryCompiler(
            new FilterCompiler,
            new DatasetRegistry,
        );
    }

    public function test_denied_scope_produces_an_impossible_predicate(): void
    {
        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['transaction_reference'],
        );

        $compiled = $this->compiler()->compile(
            new TransactionDatasetSource,
            $query,
            DatasetRowScope::denied(),
        );

        $this->assertStringContainsString(
            ' WHERE 1 = 0 LIMIT ?',
            $compiled->sql,
        );

        $this->assertSame([100], $compiled->bindings);
    }

    public function test_user_filter_cannot_replace_mandatory_branch_scope(): void
    {
        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['transaction_reference'],
            filters: [
                $this->filter(
                    'branch',
                    'equals',
                    'ANOTHER-BRANCH',
                ),
            ],
        );

        $compiled = $this->compiler()->compile(
            new TransactionDatasetSource,
            $query,
            DatasetRowScope::branch(42),
        );

        $this->assertStringContainsString(
            'WHERE branches.id = ? AND branches.branch_code = ?',
            $compiled->sql,
        );

        $this->assertSame(
            [42, 'ANOTHER-BRANCH', 100],
            $compiled->bindings,
        );
    }

    public function test_dataset_queries_default_to_utc_reporting_timezone(): void
    {
        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: ['transaction_reference'],
        );

        $this->assertSame(
            'UTC',
            $query->reportingTimezone->name,
        );
    }

    public function test_transaction_measure_can_be_grouped_by_local_booking_month(): void
    {
        $query = new DatasetQuery(
            dataset: DatasetKey::TRANSACTIONS,
            dimensions: [
                'booking_month',
                'currency',
            ],
            measures: ['total_amount'],
            reportingTimezone: new ReportingTimezone(
                'Europe/Luxembourg',
            ),
        );

        $compiled = $this->compiler()->compile(
            new TransactionDatasetSource,
            $query,
            DatasetRowScope::unrestricted(),
        );

        $monthExpression =
            "CAST(date_trunc('month', timezone('Europe/Luxembourg', transactions.booked_at)) AS date)";

        $this->assertStringContainsString(
            "{$monthExpression} AS booking_month",
            $compiled->sql,
        );

        $this->assertStringContainsString(
            "GROUP BY {$monthExpression}, transactions.currency",
            $compiled->sql,
        );

        $this->assertSame([100], $compiled->bindings);
    }

    public function test_nullable_dimension_can_use_a_left_join(): void
    {
        $source = new class implements DatasetSource
        {
            public function dataset(): DatasetKey
            {
                return DatasetKey::TRANSACTIONS;
            }

            public function connectionName(): string
            {
                return 'warehouse';
            }

            public function baseTable(): string
            {
                return 'fact_transactions';
            }

            public function branchScopeColumn(): string
            {
                return 'fact_transactions.branch_key';
            }

            public function dimensionSources(): array
            {
                return [
                    'value_date' => new DimensionSource(
                        column: 'dim_date.calendar_date',
                    ),
                ];
            }

            public function dimensionSource(
                string $dimension,
            ): DimensionSource {
                return $this->dimensionSources()[$dimension];
            }

            public function measureSources(): array
            {
                return [];
            }

            public function measureSource(
                string $measure,
            ): MeasureSource {
                throw new LogicException(
                    "No measure source [{$measure}].",
                );
            }

            public function joins(): array
            {
                return [
                    new JoinDefinition(
                        table: 'dim_date',
                        leftColumn: 'fact_transactions.value_date_key',
                        rightColumn: 'dim_date.date_key',
                        type: JoinType::LEFT,
                    ),
                ];
            }
        };

        $compiled = $this->compiler()->compile(
            $source,
            new DatasetQuery(
                dataset: DatasetKey::TRANSACTIONS,
                dimensions: ['value_date'],
            ),
            DatasetRowScope::unrestricted(),
        );

        $this->assertSame(
            'SELECT dim_date.calendar_date AS value_date FROM fact_transactions LEFT JOIN dim_date ON fact_transactions.value_date_key = dim_date.date_key LIMIT ?',
            $compiled->sql,
        );

        $this->assertSame([100], $compiled->bindings);
    }

    public function test_unused_warehouse_joins_are_omitted(): void
    {
        $compiled = $this->compiler()->compile(
            new WarehouseTransactionDatasetSource,
            new DatasetQuery(
                dataset: DatasetKey::TRANSACTIONS,
                dimensions: [
                    'branch',
                    'currency',
                ],
                measures: [
                    'transaction_count',
                    'net_cash_flow',
                ],
            ),
            DatasetRowScope::branch(2),
        );

        $this->assertStringContainsString(
            'INNER JOIN dim_branch ON fact_transactions.branch_key = dim_branch.branch_key',
            $compiled->sql,
        );

        $this->assertStringNotContainsString(
            'JOIN dim_account',
            $compiled->sql,
        );

        $this->assertStringNotContainsString(
            'JOIN dim_date',
            $compiled->sql,
        );
    }
}
