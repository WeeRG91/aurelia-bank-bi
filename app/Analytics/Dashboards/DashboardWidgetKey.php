<?php

namespace App\Analytics\Dashboards;

enum DashboardWidgetKey: string
{
    case TRANSACTION_SUMMARY = 'transaction_summary';
    case DAILY_CASH_FLOW = 'daily_cash_flow';
    case TRANSACTION_MIX = 'transaction_mix';
}
