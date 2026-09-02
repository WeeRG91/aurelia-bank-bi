export type FieldDataType = 'string' | 'date' | 'datetime' | 'boolean' | 'integer' | 'decimal';

export type DimensionKind = 'identifier' | 'categorical' | 'temporal' | 'geographic';

export type SensitivityLevel = 'internal' | 'confidential' | 'restricted';

export type AggregationFunction = 'count' | 'sum' | 'average' | 'minimum' | 'maximum';

export interface DimensionDefinition {
    key: string;
    label: string;
    description: string;
    dataType: FieldDataType;
    kind: DimensionKind;
    sensitivity: SensitivityLevel;
    nullable: boolean;
}

export interface MeasureDefinition {
    key: string;
    label: string;
    description: string;
    dataType: 'integer' | 'decimal';
    aggregation: AggregationFunction;
    sensitivity: SensitivityLevel;
    currencyDimension: string | null;
    requiredDimensions: string[];
}

export interface DatasetSummary {
    key: string;
    label: string;
    description: string;
    grain: string;
    dimensions: DimensionDefinition[];
    measures: MeasureDefinition[];
}

export interface ReportBuilderBootstrap {
    reportingTimezone: string;
    previewUrl: string;
    datasets: DatasetSummary[];
}

export interface ReportPreviewPayload {
    dataset: string;
    dimensions: string[];
    measures: string[];
    limit: number;
}

export type ReportPreviewRow = Record<string, unknown>;

export interface ReportPreviewMeta {
    dataset: string;
    dimensions: string[];
    measures: string[];
    rowCount: number;
    limit: number;
    reportingTimezone: string;
}

export interface ReportPreviewResponse {
    data: ReportPreviewRow[];
    meta: ReportPreviewMeta;
}

export interface ValidationErrorResponse {
    message?: string;
    errors?: Record<string, string[]>;
}
