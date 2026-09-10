export type DashboardWidgetKey = 'transaction_summary' | 'daily_cash_flow' | 'transaction_mix';

export type DashboardPeriod = 'last_7_days' | 'last_30_days' | 'month_to_date' | 'year_to_date';

export type DashboardRow = Record<string, unknown>;

export interface DashboardPeriodOption {
    value: DashboardPeriod;
    label: string;
}

export interface DashboardWidgetPayload {
    key: DashboardWidgetKey;
    label: string;
    description: string;
    dataset: string;
    period: DashboardPeriod;
    rows: DashboardRow[];
}

export interface DashboardBootstrap {
    dashboardUrl: string;
    reportingTimezone: string;
    selectedPeriod: DashboardPeriod;
    periods: DashboardPeriodOption[];
    widgets: DashboardWidgetPayload[];
}
