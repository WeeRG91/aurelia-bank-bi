<?php

namespace App\Analytics\Dashboards;

enum DashboardWidgetType: string
{
    case KPI = 'kpi';
    case CHART = 'chart';
    case TABLE = 'table';
}
