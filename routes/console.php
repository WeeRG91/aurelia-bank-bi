<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reports:expire-exports')
    ->dailyAt('02:15')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('horizon:snapshot')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('reports:dispatch-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('warehouse:load-transactions')
    ->dailyAt(
        (string) config(
            'analytics.warehouse.transactions_etl_time',
            '01:30',
        ),
    )
    ->timezone('UTC')
    ->withoutOverlapping(120)
    ->onOneServer();

Schedule::command(
    'warehouse:refresh-daily-transaction-aggregate',
)
    ->dailyAt(
        (string) config(
            'analytics.warehouse.daily_transaction_aggregate_refresh_time',
            '02:00',
        ),
    )
    ->timezone('UTC')
    ->withoutOverlapping(120)
    ->onOneServer();
