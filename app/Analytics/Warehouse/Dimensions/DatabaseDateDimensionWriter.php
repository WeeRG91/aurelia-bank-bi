<?php

namespace App\Analytics\Warehouse\Dimensions;

use Illuminate\Database\DatabaseManager;
use Throwable;

final readonly class DatabaseDateDimensionWriter implements DateDimensionWriter
{
    /**
     * @var list<string>
     */
    private const array UPDATE_COLUMNS = [
        'calendar_date',
        'calendar_year',
        'calendar_quarter',
        'calendar_month',
        'month_name',
        'iso_year',
        'iso_week',
        'day_of_month',
        'iso_weekday',
        'is_weekend',
    ];

    public function __construct(
        private DatabaseManager $database,
    ) {}

    /**
     * @throws Throwable
     */
    public function transaction(callable $callback): mixed
    {
        return $this->database
            ->connection('warehouse')
            ->transaction(
                static fn (): mixed => $callback(),
            );
    }

    public function upsert(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $this->database
            ->connection('warehouse')
            ->table('dim_date')
            ->upsert(
                $rows,
                ['date_key'],
                self::UPDATE_COLUMNS,
            );
    }
}
