<?php

namespace Tests\Unit;

use App\Analytics\Queries\Sources\DimensionSource;
use App\Analytics\Queries\Sources\DimensionSourceKind;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DimensionSourceTest extends TestCase
{
    public function test_dimension_source_kind_values_are_stable(): void
    {
        $this->assertSame(
            [
                'column',
                'local_date',
                'local_month',
                'local_quarter',
                'local_year',
            ],
            array_column(DimensionSourceKind::cases(), 'value'),
        );
    }

    public function test_plain_column_is_the_default_source_kind(): void
    {
        $source = new DimensionSource(
            column: 'transactions.booked_at',
        );

        $this->assertSame(
            'transactions.booked_at',
            $source->column,
        );

        $this->assertSame(
            DimensionSourceKind::COLUMN,
            $source->kind,
        );
    }

    public function test_derived_local_period_source_is_supported(): void
    {
        $source = new DimensionSource(
            column: 'transactions.booked_at',
            kind: DimensionSourceKind::LOCAL_MONTH,
        );

        $this->assertSame(
            DimensionSourceKind::LOCAL_MONTH,
            $source->kind,
        );
    }

    #[DataProvider('unsafeColumns')]
    public function test_arbitrary_sql_cannot_become_a_dimension_source(
        string $column,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new DimensionSource($column);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsafeColumns(): iterable
    {
        yield 'unqualified column' => ['booked_at'];
        yield 'function call' => ["date_trunc('month', booked_at)"];
        yield 'injected statement' => ['transactions.booked_at; DROP TABLE users'];
        yield 'quoted identifier' => ['"transactions"."booked_at"'];
        yield 'blank value' => [''];
    }

    public function test_dimension_source_is_final_and_immutable(): void
    {
        $reflection = new ReflectionClass(DimensionSource::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }
}
