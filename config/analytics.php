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
];
