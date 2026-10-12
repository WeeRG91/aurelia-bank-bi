<?php

return [
    'reporting_timezone' => env(
        'ANALYTICS_REPORTING_TIMEZONE',
        'Europe/Luxembourg',
    ),

    'export_disk' => env('REPORT_EXPORT_DISK', 'report_exports'),

    'export_retention_days' => (int) env(
        'REPORT_EXPORT_RETENTION_DAYS',
        7,
    ),

    'dashboard_cache_ttl_seconds' => (int) env(
        'ANALYTICS_DASHBOARD_CACHE_TTL_SECONDS',
        300,
    ),

    'warehouse' => [
        'transactions_etl_time' => env(
            'ANALYTICS_TRANSACTIONS_ETL_TIME',
            '01:30',
        ),

        'date_dimension_start' => env(
            'ANALYTICS_DATE_DIMENSION_START',
            '2020-01-01',
        ),

        'date_dimension_end' => env(
            'ANALYTICS_DATE_DIMENSION_END',
            '2035-12-31',
        ),
    ],
];
