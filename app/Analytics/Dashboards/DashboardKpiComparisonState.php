<?php

namespace App\Analytics\Dashboards;

enum DashboardKpiComparisonState: string
{
    case COMPARABLE = 'comparable';
    case MISSING_CURRENT = 'missing_current';
    case MISSING_PREVIOUS = 'missing_previous';
    case PREVIOUS_ZERO = 'previous_zero';
}
