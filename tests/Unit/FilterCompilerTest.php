<?php

namespace Tests\Unit;

use App\Analytics\Datasets\DatasetRegistry;
use App\Analytics\Filters\DimensionFilterRules;
use App\Analytics\Filters\FilterValidator;
use App\Analytics\Queries\Compilation\CompiledFilter;
use App\Analytics\Queries\Compilation\FilterCompiler;
use App\Analytics\Queries\Sources\TransactionDatasetSource;
use LogicException;
use PHPUnit\Framework\TestCase;

class FilterCompilerTest extends TestCase
{
    public function test_scalar_filter_is_compiled_with_a_binding(): void
    {
        $compiled = $this->compile(
            'currency',
            'equals',
            'EUR',
        );

        $this->assertSame(
            'transactions.currency = ?',
            $compiled->sql,
        );

        $this->assertSame(['EUR'], $compiled->bindings);
    }

    public function test_list_filter_uses_one_placeholder_per_value(): void
    {
        $compiled = $this->compile(
            'status',
            'in',
            ['pending', 'booked', 'failed'],
        );

        $this->assertSame(
            'transactions.status IN (?, ?, ?)',
            $compiled->sql,
        );

        $this->assertSame(
            ['pending', 'booked', 'failed'],
            $compiled->bindings,
        );
    }

    public function test_between_filter_has_two_ordered_bindings(): void
    {
        $compiled = $this->compile(
            'value_date',
            'between',
            ['2026-09-01', '2026-09-30'],
        );

        $this->assertSame(
            'transactions.value_date BETWEEN ? AND ?',
            $compiled->sql,
        );

        $this->assertSame(
            ['2026-09-01', '2026-09-30'],
            $compiled->bindings,
        );
    }

    public function test_null_filter_has_no_bindings(): void
    {
        $compiled = $this->compile(
            'booked_at',
            'is_null',
        );

        $this->assertSame(
            'transactions.booked_at IS NULL',
            $compiled->sql,
        );

        $this->assertSame([], $compiled->bindings);
    }

    public function test_joined_dimension_uses_its_allowlisted_column(): void
    {
        $compiled = $this->compile(
            'branch',
            'equals',
            'LUX-CENTRAL',
        );

        $this->assertSame(
            'branches.branch_code = ?',
            $compiled->sql,
        );

        $this->assertSame(
            ['LUX-CENTRAL'],
            $compiled->bindings,
        );
    }

    public function test_filter_value_is_never_interpolated_into_sql(): void
    {
        $maliciousValue = "EUR' OR 1=1 --";

        $compiled = $this->compile(
            'currency',
            'equals',
            $maliciousValue,
        );

        $this->assertSame(
            'transactions.currency = ?',
            $compiled->sql,
        );

        $this->assertStringNotContainsString(
            $maliciousValue,
            $compiled->sql,
        );

        $this->assertSame(
            [$maliciousValue],
            $compiled->bindings,
        );
    }

    public function test_filter_cannot_be_compiled_against_another_dataset_source(): void
    {
        $condition = $this->validator()->validate(
            'account_balances',
            'currency',
            'equals',
            'EUR',
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Filter dataset [account_balances] does not match source dataset [transactions].',
        );

        (new FilterCompiler)->compile(
            new TransactionDatasetSource,
            $condition,
        );
    }

    public function test_compiled_filter_rejects_placeholder_mismatch(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Compiled filter placeholders must match its bindings.',
        );

        new CompiledFilter(
            sql: 'transactions.currency = ?',
            bindings: [],
        );
    }

    private function compile(
        string $dimension,
        string $operator,
        mixed $value = null,
    ): CompiledFilter {
        $condition = $this->validator()->validate(
            'transactions',
            $dimension,
            $operator,
            $value,
        );

        return (new FilterCompiler)->compile(
            new TransactionDatasetSource,
            $condition,
        );
    }

    private function validator(): FilterValidator
    {
        return new FilterValidator(
            new DatasetRegistry,
            new DimensionFilterRules,
        );
    }
}
