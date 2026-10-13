<?php

namespace App\Analytics\Dashboards;

use InvalidArgumentException;

final readonly class DashboardWidgetLayout
{
    private const int GRID_COLUMNS = 12;

    public function __construct(
        public int $column,
        public int $row,
        public int $width,
        public int $height,
    ) {
        if (
            $this->column < 0
            || $this->column >= self::GRID_COLUMNS
            || $this->row < 0
        ) {
            throw new InvalidArgumentException(
                'Dashboard widget grid positions are invalid.',
            );
        }

        if (
            $this->width < 1
            || $this->width > self::GRID_COLUMNS
            || $this->height < 1
            || $this->height > self::GRID_COLUMNS
        ) {
            throw new InvalidArgumentException(
                'Dashboard widget width and height must be between 1 and 12.',
            );
        }

        if ($this->column + $this->width > self::GRID_COLUMNS) {
            throw new InvalidArgumentException(
                'Dashboard widget layout exceeds the twelve-column grid.',
            );
        }
    }

    /**
     * @return array{
     *     column: int,
     *     row: int,
     *     width: int,
     *     height: int,
     * }
     */
    public function toArray(): array
    {
        return [
            'column' => $this->column,
            'row' => $this->row,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
