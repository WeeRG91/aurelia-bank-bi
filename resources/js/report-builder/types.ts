export interface DatasetSummary {
    key: string;
    label: string;
    description: string;
    grain: string;
}

export interface ReportBuilderBootstrap {
    reportingTimezone: string;
    datasets: DatasetSummary[];
}
