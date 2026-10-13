<?php

namespace Tests\Unit;

use App\Analytics\Dashboards\DashboardWidgetLayout;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DashboardWidgetLayoutTest extends TestCase
{
    public function test_valid_layout_is_canonicalized(): void
    {
        $layout = new DashboardWidgetLayout(
            column: 3,
            row: 2,
            width: 6,
            height: 4,
        );

        $this->assertSame(
            [
                'column' => 3,
                'row' => 2,
                'width' => 6,
                'height' => 4,
            ],
            $layout->toArray(),
        );
    }

    #[DataProvider('invalidPositionProvider')]
    public function test_invalid_positions_are_rejected(
        int $column,
        int $row,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new DashboardWidgetLayout(
            column: $column,
            row: $row,
            width: 1,
            height: 1,
        );
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function invalidPositionProvider(): iterable
    {
        yield 'negative column' => [-1, 0];
        yield 'column outside grid' => [12, 0];
        yield 'negative row' => [0, -1];
    }

    #[DataProvider('invalidSizeProvider')]
    public function test_invalid_sizes_are_rejected(
        int $width,
        int $height,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new DashboardWidgetLayout(
            column: 0,
            row: 0,
            width: $width,
            height: $height,
        );
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function invalidSizeProvider(): iterable
    {
        yield 'zero width' => [0, 1];
        yield 'excessive width' => [13, 1];
        yield 'zero height' => [1, 0];
        yield 'excessive height' => [1, 13];
    }

    public function test_widget_cannot_overflow_the_grid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DashboardWidgetLayout(
            column: 8,
            row: 0,
            width: 5,
            height: 1,
        );
    }
}
