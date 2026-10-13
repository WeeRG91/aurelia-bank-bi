<?php

namespace App\Analytics\Dashboards;

enum DashboardWidgetExecutionStatus: string
{
    case AVAILABLE = 'available';
    case UNAVAILABLE = 'unavailable';
}
