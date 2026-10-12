<?php

namespace Tests\Unit;

use App\Analytics\Warehouse\Aggregates\DailyTransactionAggregateRefresher;
use App\Analytics\Warehouse\Aggregates\DatabaseDailyTransactionAggregateRefresher;
use Tests\TestCase;

final class DailyTransactionAggregateBindingTest extends TestCase
{
    public function test_daily_transaction_aggregate_refresher_is_resolvable(): void
    {
        $this->assertInstanceOf(
            DatabaseDailyTransactionAggregateRefresher::class,
            $this->app->make(
                DailyTransactionAggregateRefresher::class,
            ),
        );
    }
}
