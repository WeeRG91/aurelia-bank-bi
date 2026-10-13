export type EmployeeDashboardWidgetType = 'kpi' | 'chart' | 'table';

export type EmployeeDashboardWidgetStatus = 'available' | 'unavailable';

export type KpiComparisonState =
    | 'comparable'
    | 'missing_current'
    | 'missing_previous'
    | 'previous_zero';

export type DashboardResultRow = Record<string, unknown>;

export type KpiContext = Record<string, boolean | number | string | null>;

export interface EmployeeDashboardLayout {
    column: number;
    row: number;
    width: number;
    height: number;
}

export interface EmployeeDashboardWidgetConfiguration {
    show_comparison?: boolean;
    row_limit?: number;
}

export interface EmployeeDashboardWidget {
    id: number;
    dashboardId: number;
    savedReportId: number;
    title: string;
    type: EmployeeDashboardWidgetType;
    layout: EmployeeDashboardLayout;
    configuration: EmployeeDashboardWidgetConfiguration | null;
    createdAt: string | null;
    updatedAt: string | null;
    updateUrl: string;
    destroyUrl: string;
}

export interface EmployeeDashboard {
    id: number;
    name: string;
    description: string | null;
    isDefault: boolean;
    widgetCount: number;
    widgets?: EmployeeDashboardWidget[];
    createdAt: string | null;
    updatedAt: string | null;
    showUrl: string;
    resultsUrl: string;
    updateUrl: string;
    destroyUrl: string;
    widgetStoreUrl: string;
}

export interface EmployeeDashboardKpiValue {
    context: KpiContext;
    value: number | string | null;
}

export interface EmployeeDashboardKpiComparison {
    context: KpiContext;
    current: number | string | null;
    previous: number | string | null;
    state: KpiComparisonState;
    percentage_change: string | null;
}

export interface EmployeeDashboardWidgetResult {
    widgetId: number;
    type: EmployeeDashboardWidgetType;
    status: EmployeeDashboardWidgetStatus;
    dataset: string | null;
    dimensions: string[];
    measures: string[];
    rowCount: number;
    rows: DashboardResultRow[];
    valueCount: number;
    values: EmployeeDashboardKpiValue[];
    comparisonCount: number;
    comparisons: EmployeeDashboardKpiComparison[];
    visualization: EmployeeDashboardVisualization | null;
}

export interface EmployeeDashboardResultsMeta {
    dashboardId: number;
    generatedAt: string;
    reportingTimezone: string;
    widgetCount: number;
}

export interface EmployeeDashboardResultsResponse {
    data: EmployeeDashboardWidgetResult[];
    meta: EmployeeDashboardResultsMeta;
}

export interface EmployeeDashboardResponse {
    data: EmployeeDashboard;
}

export interface EmployeeDashboardCollectionResponse {
    data: EmployeeDashboard[];
}

export interface EmployeeDashboardBootstrap {
    indexUrl: string;
    storeUrl: string;
    savedReports: EmployeeDashboardSavedReportOption[];
}

export interface EmployeeDashboardSavedReportOption {
    id: number;
    name: string;
    dataset: string;
}

export interface EmployeeDashboardWidgetResponse {
    data: EmployeeDashboardWidget;
}

export interface EmployeeDashboardVisualization {
    type: 'bar' | 'line';
    dimension: string;
    measure: string;
    series: string | null;
}
