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
use App\Analytics\Queries\Sources\AccountBalanceDatasetSource;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AccountBalanceMeasureQueryCompilerTest extends TestCase
{
    public function test_balance_measures_compile_at_safe_point_in_time_grain(): void
    {
        $compiled = $this->compile(
            new DatasetQuery(
                dataset: DatasetKey::ACCOUNT_BALANCES,
                dimensions: [
                    'branch',
                    'currency',
                    'snapshot_date',
                ],
                measures: [
                    'snapshot_count',
                    'total_ledger_balance',
                    'average_available_balance',
                ],
            ),
            DatasetRowScope::branch(42),
        );

        $this->assertStringContainsString(
            'SUM(account_balance_snapshots.ledger_balance) AS total_ledger_balance',
            $compiled->sql,
        );

        $this->assertStringContainsString(
            'AVG(account_balance_snapshots.available_balance) AS average_available_balance',
            $compiled->sql,
        );

        $this->assertStringContainsString(
            'GROUP BY branches.branch_code, accounts.currency, account_balance_snapshots.snapshot_date',
            $compiled->sql,
        );

        $this->assertSame([42, 100], $compiled->bindings);
    }

    public function test_balance_measure_rejects_multiple_snapshot_dates(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Measure [total_ledger_balance] requires dimension [snapshot_date] or a single-value filter.',
        );

        $this->compile(
            new DatasetQuery(
                dataset: DatasetKey::ACCOUNT_BALANCES,
                dimensions: ['branch', 'currency'],
                measures: ['total_ledger_balance'],
            ),
        );
    }

    public function test_balance_measure_accepts_a_single_snapshot_filter(): void
    {
        $compiled = $this->compile(
            new DatasetQuery(
                dataset: DatasetKey::ACCOUNT_BALANCES,
                dimensions: ['branch', 'currency'],
                measures: ['total_available_balance'],
                filters: [
                    $this->filter(
                        'snapshot_date',
                        'equals',
                        '2026-08-31',
                    ),
                ],
            ),
        );

        $this->assertStringContainsString(
            'account_balance_snapshots.snapshot_date = ?',
            $compiled->sql,
        );

        $this->assertSame(
            ['2026-08-31', 100],
            $compiled->bindings,
        );
    }

    public function test_balance_measure_rejects_cross_currency_total(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->compile(
            new DatasetQuery(
                dataset: DatasetKey::ACCOUNT_BALANCES,
                dimensions: ['snapshot_date'],
                measures: ['total_ledger_balance'],
            ),
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
            new AccountBalanceDatasetSource,
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
            DatasetKey::ACCOUNT_BALANCES,
            $dimension,
            $operator,
            $value,
        );
    }
}
