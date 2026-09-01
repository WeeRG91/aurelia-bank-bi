<?php

namespace Tests\Unit;

use App\Analytics\Queries\Sources\MeasureSource;
use App\Analytics\Queries\Sources\MeasureSourceKind;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MeasureSourceTest extends TestCase
{
    public function test_plain_column_source_is_valid(): void
    {
        $source = new MeasureSource(
            column: 'transactions.amount',
        );

        $this->assertSame(
            MeasureSourceKind::COLUMN,
            $source->kind,
        );

        $this->assertNull($source->directionColumn);
    }

    public function test_directional_source_requires_direction_column(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Directional measure source requires a direction column.',
        );

        new MeasureSource(
            column: 'transactions.amount',
            kind: MeasureSourceKind::INCOMING_AMOUNT,
        );
    }

    public function test_plain_source_rejects_direction_column(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Plain column measure source cannot define a direction column.',
        );

        new MeasureSource(
            column: 'transactions.amount',
            directionColumn: 'transactions.direction',
        );
    }

    public function test_source_rejects_unsafe_column_identifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid qualified measure column [amount); DROP TABLE users; --].',
        );

        new MeasureSource(
            column: 'amount); DROP TABLE users; --',
        );
    }
}
