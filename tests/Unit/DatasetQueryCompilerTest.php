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
use App\Analytics\Queries\Sources\TransactionDatasetSource;
use InvalidArgumentException;
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
}
