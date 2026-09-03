<script setup lang="ts">
import { computed, ref } from 'vue';

import type {
    ChartConfiguration,
    ChartType,
    DatasetSummary,
    DimensionDefinition,
    FilterOperator,
    MeasureDefinition,
    RelativeDatePreset,
    RelativeDateSelection,
    ReportBuilderBootstrap,
    ReportFilterDraft,
    ReportFilterPayload,
    ReportPreviewPayload,
    ReportPreviewResponse,
    SavedReportData,
    StoreSavedReportPayload,
    StoreSavedReportResponse,
    ValidationErrorResponse,
} from './types';
import { axios } from '../bootstrap';
import ReportChart from '@/report-builder/ReportChart.vue';

const MAX_DIMENSIONS = 20;
const MAX_MEASURES = 10;
const MAX_FILTERS = 20;

const FILTER_OPERATOR_LABELS: Record<FilterOperator, string> = {
    equals: 'Equals',
    not_equals: 'Does not equal',
    in: 'Is one of',
    not_in: 'Is not one of',
    before: 'Before',
    after: 'After',
    on_or_after: 'On or after',
    between: 'Between',
    is_null: 'Is empty',
    is_not_null: 'Is not empty',
};

const RELATIVE_DATE_PRESET_LABELS: Record<RelativeDatePreset, string> = {
    today: 'Today',
    yesterday: 'Yesterday',
    last_7_days: 'Last 7 days',
    last_30_days: 'Last 30 days',
    month_to_date: 'Month to date',
    previous_month: 'Previous month',
    quarter_to_date: 'Quarter to date',
    previous_quarter: 'Previous quarter',
    year_to_date: 'Year to date',
};

const props = defineProps<{
    bootstrap: ReportBuilderBootstrap;
}>();

const initialReport = props.bootstrap.initialReport;

let nextFilterId = 1;

function toFilterDraft(filter: ReportFilterPayload): ReportFilterDraft {
    let value = '';
    let upperValue = '';

    if (Array.isArray(filter.value)) {
        if (filter.operator === 'between') {
            value = String(filter.value[0] ?? '');
            upperValue = String(filter.value[1] ?? '');
        } else {
            value = filter.value.map(String).join(', ');
        }
    } else if (filter.value !== null) {
        value = String(filter.value);
    }

    return {
        id: nextFilterId++,
        dimension: filter.dimension,
        operator: filter.operator,
        value,
        upperValue,
    };
}

const selectedDatasetKey = ref(initialReport?.dataset ?? props.bootstrap.datasets[0]?.key ?? '');
const selectedDimensionKeys = ref<string[]>(initialReport?.definition.dimensions ?? []);
const selectedMeasureKeys = ref<string[]>(initialReport?.definition.measures ?? []);
const selectedFilters = ref<ReportFilterDraft[]>(
    initialReport?.definition.filters.map(toFilterDraft) ?? [],
);
const selectedRelativeDateDimension = ref<string>(
    initialReport?.definition.relative_date?.dimension ?? '',
);
const selectedRelativeDatePreset = ref<RelativeDatePreset | ''>(
    initialReport?.definition.relative_date?.preset ?? '',
);

const reportLimit = ref<number>(initialReport?.definition.limit ?? 100);

const reportName = ref<string>(initialReport?.name ?? '');
const reportDescription = ref<string>(initialReport?.description ?? '');

const editingReport = ref<SavedReportData | null>(initialReport);
const isSaveLoading = ref<boolean>(false);
const saveError = ref<string | null>(null);
const saveSuccessMessage = ref<string | null>(null);

const isPreviewLoading = ref<boolean>(false);
const preview = ref<ReportPreviewResponse | null>(null);
const previewError = ref<string | null>(null);

const initialVisualization = initialReport?.definition.visualization;

const chartType = ref<ChartType>(initialVisualization?.type ?? 'bar');
const chartDimension = ref<string>(initialVisualization?.dimension ?? '');
const chartMeasure = ref<string>(initialVisualization?.measure ?? '');
const chartSeries = ref<string | null>(initialVisualization?.series ?? null);

const previewColumns = computed<string[]>(() => {
    if (preview.value === null) {
        return [];
    }

    return [...preview.value.meta.dimensions, ...preview.value.meta.measures];
});

const selectedDataset = computed<DatasetSummary | undefined>(() =>
    props.bootstrap.datasets.find((dataset) => dataset.key === selectedDatasetKey.value),
);

const canUseLineChart = computed<boolean>(() => isTemporalDimension(chartDimension.value));

const canRenderChart = computed<boolean>(
    () =>
        preview.value !== null &&
        preview.value.data.length > 0 &&
        chartDimension.value !== '' &&
        chartMeasure.value !== '',
);

const chartSeriesOptions = computed<string[]>(
    () =>
        preview.value?.meta.dimensions.filter((dimension) => dimension !== chartDimension.value) ??
        [],
);

const relativeDateDimensions = computed<DimensionDefinition[]>(
    () =>
        selectedDataset.value?.dimensions.filter(
            (dimension) => dimension.dataType === 'date' || dimension.dataType === 'datetime',
        ) ?? [],
);

const explicitlyFilteredDimensionKeys = computed<Set<string>>(
    () => new Set(selectedFilters.value.map((filter) => filter.dimension)),
);

const requiredDimensionKeys = computed<Set<string>>(() => {
    const measures = selectedDataset.value?.measures ?? [];

    return new Set(
        measures
            .filter((measure) => selectedMeasureKeys.value.includes(measure.key))
            .flatMap((measure) => measure.requiredDimensions),
    );
});

function clearPreview(): void {
    preview.value = null;
    previewError.value = null;
    saveError.value = null;
    saveSuccessMessage.value = null;
}

function clearSaveFeedback(): void {
    saveError.value = null;
    saveSuccessMessage.value = null;
}

function dimensionForFilter(filter: ReportFilterDraft): DimensionDefinition | undefined {
    return selectedDataset.value?.dimensions.find(
        (dimension) => dimension.key === filter.dimension,
    );
}

function addFilter(): void {
    const dimension = selectedDataset.value?.dimensions.find(
        (candidate) => candidate.key !== selectedRelativeDateDimension.value,
    );
    const operator = dimension?.allowedOperators[0];

    if (
        dimension === undefined ||
        operator === undefined ||
        selectedFilters.value.length >= MAX_FILTERS
    ) {
        return;
    }

    selectedFilters.value = [
        ...selectedFilters.value,
        {
            id: nextFilterId++,
            dimension: dimension.key,
            operator,
            value: '',
            upperValue: '',
        },
    ];

    clearPreview();
}

function removeFilter(filterId: number): void {
    selectedFilters.value = selectedFilters.value.filter((filter) => filter.id !== filterId);

    clearPreview();
}

function changeFilterDimension(filter: ReportFilterDraft): void {
    const dimension = dimensionForFilter(filter);
    const operator = dimension?.allowedOperators[0];

    if (operator !== undefined) {
        filter.operator = operator;
    }

    filter.value = '';
    filter.upperValue = '';
    clearPreview();
}

function changeFilterOperator(filter: ReportFilterDraft): void {
    filter.value = '';
    filter.upperValue = '';
    clearPreview();
}

function operatorNeedsNoValue(operator: FilterOperator): boolean {
    return operator === 'is_null' || operator === 'is_not_null';
}

function operatorUsesList(operator: FilterOperator): boolean {
    return operator === 'in' || operator === 'not_in';
}

function operatorUsesRange(operator: FilterOperator): boolean {
    return operator === 'between';
}

function filterInputType(filter: ReportFilterDraft): string {
    const dataType = dimensionForFilter(filter)?.dataType;

    if (dataType === 'date') {
        return 'date';
    }

    if (dataType === 'integer') {
        return 'number';
    }

    return 'text';
}

function filterPlaceholder(filter: ReportFilterDraft): string {
    const dataType = dimensionForFilter(filter)?.dataType;

    if (dataType === 'datetime') {
        return '2026-09-01T12:30:00+02:00';
    }

    return operatorUsesList(filter.operator) ? 'EUR, USD, GBP' : 'Enter a value';
}

function normalizeFilterScalar(
    filter: ReportFilterDraft,
    value: string,
): string | number | boolean {
    const dataType = dimensionForFilter(filter)?.dataType;

    if (dataType === 'integer' && value.trim() !== '') {
        return Number.parseInt(value, 10);
    }

    if (dataType === 'boolean') {
        return value === 'true';
    }

    return value;
}

function toFilterPayload(filter: ReportFilterDraft): ReportFilterPayload {
    if (operatorNeedsNoValue(filter.operator)) {
        return {
            dimension: filter.dimension,
            operator: filter.operator,
            value: null,
        };
    }

    if (operatorUsesList(filter.operator)) {
        return {
            dimension: filter.dimension,
            operator: filter.operator,
            value: filter.value
                .split(',')
                .map((value) => value.trim())
                .filter((value) => value !== '')
                .map((value) => normalizeFilterScalar(filter, value)),
        };
    }

    if (operatorUsesRange(filter.operator)) {
        return {
            dimension: filter.dimension,
            operator: filter.operator,
            value: [
                normalizeFilterScalar(filter, filter.value),
                normalizeFilterScalar(filter, filter.upperValue),
            ],
        };
    }

    return {
        dimension: filter.dimension,
        operator: filter.operator,
        value: normalizeFilterScalar(filter, filter.value),
    };
}

function changeRelativeDateDimension(): void {
    if (selectedRelativeDateDimension.value === '') {
        selectedRelativeDatePreset.value = '';
        clearPreview();

        return;
    }

    if (selectedRelativeDatePreset.value === '') {
        selectedRelativeDatePreset.value = props.bootstrap.relativeDatePresets.includes(
            'last_30_days',
        )
            ? 'last_30_days'
            : (props.bootstrap.relativeDatePresets[0] ?? '');
    }

    clearPreview();
}

function changeRelativeDatePreset(): void {
    clearPreview();
}

function buildVisualization(): ChartConfiguration | null {
    if (
        chartDimension.value === '' ||
        chartMeasure.value === '' ||
        !selectedDimensionKeys.value.includes(chartDimension.value) ||
        !selectedMeasureKeys.value.includes(chartMeasure.value)
    ) {
        return null;
    }

    if (chartType.value === 'line' && !isTemporalDimension(chartDimension.value)) {
        return null;
    }

    return {
        type: chartType.value,
        dimension: chartDimension.value,
        measure: chartMeasure.value,
        series: chartSeries.value,
    };
}

function buildReportPayload(): ReportPreviewPayload | null {
    if (selectedDataset.value === undefined) {
        return null;
    }

    const relativeDateSelection: RelativeDateSelection | null =
        selectedRelativeDateDimension.value !== '' && selectedRelativeDatePreset.value !== ''
            ? {
                  dimension: selectedRelativeDateDimension.value,
                  preset: selectedRelativeDatePreset.value,
              }
            : null;

    return {
        dataset: selectedDataset.value.key,
        dimensions: selectedDimensionKeys.value,
        measures: selectedMeasureKeys.value,
        filters: selectedFilters.value.map(toFilterPayload),
        relative_date: relativeDateSelection,
        visualization: buildVisualization(),
        limit: reportLimit.value,
    };
}

function isTemporalDimension(dimensionKey: string): boolean {
    const dimension = selectedDataset.value?.dimensions.find(
        (candidate) => candidate.key === dimensionKey,
    );

    return dimension?.dataType === 'date' || dimension?.dataType === 'datetime';
}

function dimensionLabel(dimensionKey: string): string {
    return (
        selectedDataset.value?.dimensions.find((dimension) => dimension.key === dimensionKey)
            ?.label ?? dimensionKey
    );
}

function measureLabel(measureKey: string): string {
    return (
        selectedDataset.value?.measures.find((measure) => measure.key === measureKey)?.label ??
        measureKey
    );
}

function synchronizeChartSelection(response: ReportPreviewResponse): void {
    if (!response.meta.dimensions.includes(chartDimension.value)) {
        chartDimension.value = response.meta.dimensions[0] ?? '';
    }

    if (!response.meta.measures.includes(chartMeasure.value)) {
        chartMeasure.value = response.meta.measures[0] ?? '';
    }

    if (chartType.value === 'line' && !isTemporalDimension(chartDimension.value)) {
        chartType.value = 'bar';
    }

    if (
        chartSeries.value === chartDimension.value ||
        (chartSeries.value !== null && !response.meta.dimensions.includes(chartSeries.value))
    ) {
        chartSeries.value =
            response.meta.dimensions.find((dimension) => dimension !== chartDimension.value) ??
            null;
    }
}

function changeChartDimension(): void {
    if (chartType.value === 'line' && !isTemporalDimension(chartDimension.value)) {
        chartType.value = 'bar';
    }

    if (chartSeries.value === chartDimension.value) {
        chartSeries.value = chartSeriesOptions.value[0] ?? null;
    }

    clearSaveFeedback();
}

async function previewReport(): Promise<void> {
    if (selectedDataset.value === undefined) {
        return;
    }

    isPreviewLoading.value = true;
    previewError.value = null;
    preview.value = null;

    try {
        const payload = buildReportPayload();

        if (payload === null) {
            return;
        }

        const response = await axios.post<ReportPreviewResponse>(
            props.bootstrap.previewUrl,
            payload,
        );

        preview.value = response.data;
        synchronizeChartSelection(response.data);
    } catch (error: unknown) {
        if (axios.isAxiosError<ValidationErrorResponse>(error)) {
            const validationErrors = error.response?.data.errors;

            const firstValidationError =
                validationErrors === undefined
                    ? undefined
                    : Object.values(validationErrors).flat()[0];

            previewError.value =
                firstValidationError ??
                error.response?.data.message ??
                'The report preview could not be generated.';
        } else {
            previewError.value = 'An unexpected error occurred while generating the preview.';
        }
    } finally {
        isPreviewLoading.value = false;
    }
}

async function saveReport(): Promise<void> {
    const reportDefinition = buildReportPayload();
    const name = reportName.value.trim();

    if (reportDefinition === null || name === '') {
        return;
    }

    isSaveLoading.value = true;
    saveError.value = null;

    const description = reportDescription.value.trim();

    const payload: StoreSavedReportPayload = {
        name,
        description: description === '' ? null : description,
        ...reportDefinition,
    };

    try {
        const reportBeingEdited = editingReport.value;

        const response =
            reportBeingEdited === null
                ? await axios.post<StoreSavedReportResponse>(props.bootstrap.saveReportUrl, payload)
                : await axios.put<StoreSavedReportResponse>(reportBeingEdited.updateUrl, payload);

        editingReport.value = response.data.data;
        reportName.value = response.data.data.name;
        reportDescription.value = response.data.data.description ?? '';

        saveSuccessMessage.value =
            reportBeingEdited === null
                ? `“${response.data.data.name}” was saved successfully.`
                : `“${response.data.data.name}” was updated successfully.`;
    } catch (error: unknown) {
        if (axios.isAxiosError<ValidationErrorResponse>(error)) {
            const validationErrors = error.response?.data.errors;

            const firstValidationError =
                validationErrors === undefined
                    ? undefined
                    : Object.values(validationErrors).flat()[0];

            saveError.value =
                firstValidationError ??
                error.response?.data.message ??
                'The report could not be saved.';
        } else {
            saveError.value = 'An unexpected error occurred while saving the report.';
        }
    } finally {
        isSaveLoading.value = false;
    }
}

function formatCellValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    if (typeof value === 'string' || typeof value === 'number') {
        return String(value);
    }

    return JSON.stringify(value) ?? '—';
}

function selectDataset(datasetKey: string): void {
    selectedDatasetKey.value = datasetKey;
    selectedDimensionKeys.value = [];
    selectedMeasureKeys.value = [];
    selectedFilters.value = [];
    selectedRelativeDateDimension.value = '';
    selectedRelativeDatePreset.value = '';

    chartType.value = 'bar';
    chartDimension.value = '';
    chartMeasure.value = '';
    chartSeries.value = null;

    clearPreview();
}

function isDimensionSelected(dimensionKey: string): boolean {
    return selectedDimensionKeys.value.includes(dimensionKey);
}

function isMeasureSelected(measureKey: string): boolean {
    return selectedMeasureKeys.value.includes(measureKey);
}

function isRequiredDimension(dimensionKey: string): boolean {
    return requiredDimensionKeys.value.has(dimensionKey);
}

function toggleDimension(dimensionKey: string): void {
    if (isDimensionSelected(dimensionKey)) {
        if (isRequiredDimension(dimensionKey)) {
            return;
        }

        clearPreview();

        selectedDimensionKeys.value = selectedDimensionKeys.value.filter(
            (key) => key !== dimensionKey,
        );

        return;
    }

    if (selectedDimensionKeys.value.length >= MAX_DIMENSIONS) {
        return;
    }

    clearPreview();

    selectedDimensionKeys.value = [...selectedDimensionKeys.value, dimensionKey];
}

function toggleMeasure(measure: MeasureDefinition): void {
    if (isMeasureSelected(measure.key)) {
        clearPreview();

        selectedMeasureKeys.value = selectedMeasureKeys.value.filter((key) => key !== measure.key);

        return;
    }

    if (selectedMeasureKeys.value.length >= MAX_MEASURES) {
        return;
    }

    const missingRequiredDimensions = measure.requiredDimensions.filter(
        (key) => !isDimensionSelected(key),
    );

    if (selectedDimensionKeys.value.length + missingRequiredDimensions.length > MAX_DIMENSIONS) {
        return;
    }

    clearPreview();

    selectedMeasureKeys.value = [...selectedMeasureKeys.value, measure.key];

    selectedDimensionKeys.value = [...selectedDimensionKeys.value, ...missingRequiredDimensions];
}
</script>

<template>
    <section
        v-if="bootstrap.datasets.length === 0"
        class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm"
    >
        <h2 class="text-lg font-semibold text-slate-950">No datasets available</h2>

        <p class="mt-2 text-sm text-slate-600">
            Your employee role currently has no active analytics datasets.
        </p>
    </section>

    <section v-else class="grid gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]">
        <aside class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold tracking-wide text-slate-500 uppercase">
                Available datasets
            </h2>

            <div class="mt-4 space-y-2">
                <button
                    v-for="dataset in bootstrap.datasets"
                    :key="dataset.key"
                    type="button"
                    class="w-full rounded-lg border px-4 py-3 text-left transition"
                    :class="
                        selectedDatasetKey === dataset.key
                            ? 'border-amber-500 bg-amber-50 text-amber-950'
                            : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'
                    "
                    @click="selectDataset(dataset.key)"
                >
                    <span class="block font-medium">
                        {{ dataset.label }}
                    </span>

                    <span class="mt-1 block text-xs opacity-70">
                        {{ dataset.key }}
                    </span>
                </button>
            </div>
        </aside>

        <div v-if="selectedDataset" class="space-y-6">
            <header class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-amber-700">Selected dataset</p>

                        <h2 class="mt-1 text-2xl font-semibold text-slate-950">
                            {{ selectedDataset.label }}
                        </h2>
                    </div>

                    <span
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600"
                    >
                        {{ bootstrap.reportingTimezone }}
                    </span>
                </div>

                <p class="mt-4 text-slate-600">
                    {{ selectedDataset.description }}
                </p>

                <p class="mt-3 text-sm text-slate-500">Grain: {{ selectedDataset.grain }}</p>
            </header>

            <div class="grid gap-6 xl:grid-cols-2">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-950">Dimensions</h3>

                        <span class="text-xs text-slate-500">
                            {{ selectedDimensionKeys.length }}/{{ MAX_DIMENSIONS }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-3">
                        <label
                            v-for="dimension in selectedDataset.dimensions"
                            :key="dimension.key"
                            class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-3"
                        >
                            <input
                                type="checkbox"
                                class="mt-1 size-4 rounded border-slate-300 text-amber-600"
                                :checked="isDimensionSelected(dimension.key)"
                                :disabled="
                                    isRequiredDimension(dimension.key) ||
                                    (!isDimensionSelected(dimension.key) &&
                                        selectedDimensionKeys.length >= MAX_DIMENSIONS)
                                "
                                @change="toggleDimension(dimension.key)"
                            />

                            <span>
                                <span class="block text-sm font-medium text-slate-900">
                                    {{ dimension.label }}
                                </span>

                                <span class="mt-1 block text-xs text-slate-500">
                                    {{ dimension.kind }} ·
                                    {{ dimension.dataType }}
                                    <template v-if="isRequiredDimension(dimension.key)">
                                        · required by measure
                                    </template>
                                </span>
                            </span>
                        </label>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-950">Measures</h3>

                        <span class="text-xs text-slate-500">
                            {{ selectedMeasureKeys.length }}/{{ MAX_MEASURES }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-3">
                        <label
                            v-for="measure in selectedDataset.measures"
                            :key="measure.key"
                            class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-3"
                        >
                            <input
                                type="checkbox"
                                class="mt-1 size-4 rounded border-slate-300 text-amber-600"
                                :checked="isMeasureSelected(measure.key)"
                                :disabled="
                                    !isMeasureSelected(measure.key) &&
                                    selectedMeasureKeys.length >= MAX_MEASURES
                                "
                                @change="toggleMeasure(measure)"
                            />

                            <span>
                                <span class="block text-sm font-medium text-slate-900">
                                    {{ measure.label }}
                                </span>

                                <span class="mt-1 block text-xs text-slate-500">
                                    {{ measure.aggregation }} ·
                                    {{ measure.dataType }}
                                </span>
                            </span>
                        </label>
                    </div>
                </section>
            </div>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <h3 class="font-semibold text-slate-950">Relative date</h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Resolve a calendar period when the report runs using
                        {{ bootstrap.reportingTimezone }}.
                    </p>
                </div>

                <div
                    v-if="relativeDateDimensions.length > 0"
                    class="mt-5 grid gap-4 md:grid-cols-2"
                >
                    <label class="block">
                        <span class="text-xs font-semibold text-slate-600"> Date dimension </span>

                        <select
                            v-model="selectedRelativeDateDimension"
                            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                            @change="changeRelativeDateDimension"
                        >
                            <option value="">No relative date</option>

                            <option
                                v-for="dimension in relativeDateDimensions"
                                :key="dimension.key"
                                :value="dimension.key"
                                :disabled="explicitlyFilteredDimensionKeys.has(dimension.key)"
                            >
                                {{ dimension.label }}
                            </option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-xs font-semibold text-slate-600"> Period </span>

                        <select
                            v-model="selectedRelativeDatePreset"
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-100"
                            :disabled="selectedRelativeDateDimension === ''"
                            @change="changeRelativeDatePreset"
                        >
                            <option value="" disabled>Select a period</option>

                            <option
                                v-for="preset in bootstrap.relativeDatePresets"
                                :key="preset"
                                :value="preset"
                            >
                                {{ RELATIVE_DATE_PRESET_LABELS[preset] }}
                            </option>
                        </select>
                    </label>
                </div>

                <p v-else class="mt-5 rounded-lg bg-slate-50 p-4 text-sm text-slate-500">
                    This dataset has no date dimensions.
                </p>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-slate-950">Filters</h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Restrict rows before measures are calculated.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40"
                        :disabled="selectedFilters.length >= MAX_FILTERS"
                        @click="addFilter"
                    >
                        Add filter
                    </button>
                </div>

                <p
                    v-if="selectedFilters.length === 0"
                    class="mt-5 rounded-lg bg-slate-50 p-4 text-sm text-slate-500"
                >
                    No filters applied.
                </p>

                <div v-else class="mt-5 space-y-4">
                    <div
                        v-for="filter in selectedFilters"
                        :key="filter.id"
                        class="rounded-lg border border-slate-200 p-4"
                    >
                        <div class="grid gap-4 lg:grid-cols-[1fr_1fr_auto]">
                            <label class="block">
                                <span class="text-xs font-semibold text-slate-600">
                                    Dimension
                                </span>

                                <select
                                    v-model="filter.dimension"
                                    class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                                    @change="changeFilterDimension(filter)"
                                >
                                    <option
                                        v-for="dimension in selectedDataset.dimensions"
                                        :key="dimension.key"
                                        :value="dimension.key"
                                        :disabled="dimension.key === selectedRelativeDateDimension"
                                    >
                                        {{ dimension.label }}
                                    </option>
                                </select>
                            </label>

                            <label class="block">
                                <span class="text-xs font-semibold text-slate-600"> Operator </span>

                                <select
                                    v-model="filter.operator"
                                    class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                                    @change="changeFilterOperator(filter)"
                                >
                                    <option
                                        v-for="operator in dimensionForFilter(filter)
                                            ?.allowedOperators ?? []"
                                        :key="operator"
                                        :value="operator"
                                    >
                                        {{ FILTER_OPERATOR_LABELS[operator] }}
                                    </option>
                                </select>
                            </label>

                            <button
                                type="button"
                                class="self-end rounded-lg px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50"
                                @click="removeFilter(filter.id)"
                            >
                                Remove
                            </button>
                        </div>

                        <div
                            v-if="operatorNeedsNoValue(filter.operator)"
                            class="mt-4 text-sm text-slate-500"
                        >
                            This operator does not require a value.
                        </div>

                        <div
                            v-else-if="operatorUsesRange(filter.operator)"
                            class="mt-4 grid gap-4 sm:grid-cols-2"
                        >
                            <input
                                v-model="filter.value"
                                :type="filterInputType(filter)"
                                :placeholder="filterPlaceholder(filter)"
                                class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                @input="clearPreview"
                            />

                            <input
                                v-model="filter.upperValue"
                                :type="filterInputType(filter)"
                                :placeholder="filterPlaceholder(filter)"
                                class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                @input="clearPreview"
                            />
                        </div>

                        <textarea
                            v-else-if="operatorUsesList(filter.operator)"
                            v-model="filter.value"
                            rows="2"
                            :placeholder="filterPlaceholder(filter)"
                            class="mt-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            @input="clearPreview"
                        />

                        <select
                            v-else-if="dimensionForFilter(filter)?.dataType === 'boolean'"
                            v-model="filter.value"
                            class="mt-4 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                            @change="clearPreview"
                        >
                            <option value="">Select a value</option>
                            <option value="true">Yes</option>
                            <option value="false">No</option>
                        </select>

                        <input
                            v-else
                            v-model="filter.value"
                            :type="filterInputType(filter)"
                            :placeholder="filterPlaceholder(filter)"
                            class="mt-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            @input="clearPreview"
                        />
                    </div>
                </div>
            </section>

            <footer class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Report definition</h3>

                <p class="mt-2 text-sm text-slate-600">
                    {{ selectedDimensionKeys.length }} dimensions,
                    {{ selectedMeasureKeys.length }} measures and
                    {{ selectedFilters.length }} filters selected.
                </p>

                <p v-if="selectedRelativeDatePreset !== ''" class="mt-2 text-sm text-slate-600">
                    Relative period:
                    {{ RELATIVE_DATE_PRESET_LABELS[selectedRelativeDatePreset] }}.
                </p>

                <p v-if="editingReport !== null" class="mt-4 text-sm font-medium text-blue-700">
                    Editing saved report #{{ editingReport.id }}
                </p>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <label class="grid gap-2 text-sm font-medium text-slate-700">
                        Report name

                        <input
                            v-model="reportName"
                            type="text"
                            maxlength="150"
                            placeholder="Monthly EUR movements"
                            class="rounded-lg border border-slate-300 px-3 py-2 font-normal"
                            @input="clearSaveFeedback"
                        />
                    </label>

                    <label class="grid gap-2 text-sm font-medium text-slate-700">
                        Description

                        <input
                            v-model="reportDescription"
                            type="text"
                            maxlength="2000"
                            placeholder="Optional report description"
                            class="rounded-lg border border-slate-300 px-3 py-2 font-normal"
                            @input="clearSaveFeedback"
                        />
                    </label>
                </div>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button
                        type="button"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="
                            isPreviewLoading ||
                            (selectedDimensionKeys.length === 0 && selectedMeasureKeys.length === 0)
                        "
                        @click="previewReport"
                    >
                        {{ isPreviewLoading ? 'Generating preview…' : 'Preview report' }}
                    </button>

                    <button
                        type="button"
                        class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="
                            isSaveLoading ||
                            reportName.trim() === '' ||
                            (selectedDimensionKeys.length === 0 && selectedMeasureKeys.length === 0)
                        "
                        @click="saveReport"
                    >
                        {{
                            isSaveLoading
                                ? 'Saving report…'
                                : editingReport === null
                                  ? 'Save report'
                                  : 'Update report'
                        }}
                    </button>
                </div>

                <p
                    v-if="saveError !== null"
                    class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"
                >
                    {{ saveError }}
                </p>

                <p
                    v-if="saveSuccessMessage !== null"
                    class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800"
                >
                    {{ saveSuccessMessage }}
                </p>
            </footer>

            <section
                v-if="previewError !== null"
                class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800"
            >
                {{ previewError }}
            </section>

            <section
                v-if="preview !== null"
                class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                <header
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4"
                >
                    <div>
                        <h3 class="font-semibold text-slate-950">Report preview</h3>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ preview.meta.rowCount }} rows ·
                            {{ preview.meta.reportingTimezone }}
                        </p>
                    </div>

                    <span
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600"
                    >
                        Limit: {{ preview.meta.limit }}
                    </span>
                </header>

                <section
                    v-if="
                        preview.data.length > 0 &&
                        preview.meta.dimensions.length > 0 &&
                        preview.meta.measures.length > 0
                    "
                    class="border-b border-slate-200 p-5"
                >
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h4 class="font-semibold text-slate-950">Visualization</h4>

                            <p class="mt-1 text-sm text-slate-500">
                                Charts use the same authorized rows as the table below.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <label class="grid gap-1 text-xs font-medium text-slate-600">
                                Chart type

                                <select
                                    v-model="chartType"
                                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                                    @change="clearSaveFeedback"
                                >
                                    <option value="bar">Bar chart</option>
                                    <option value="line" :disabled="!canUseLineChart">
                                        Line chart
                                    </option>
                                </select>
                            </label>

                            <label class="grid gap-1 text-xs font-medium text-slate-600">
                                Horizontal axis

                                <select
                                    v-model="chartDimension"
                                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                                    @change="changeChartDimension"
                                >
                                    <option
                                        v-for="dimension in preview.meta.dimensions"
                                        :key="dimension"
                                        :value="dimension"
                                    >
                                        {{ dimensionLabel(dimension) }}
                                    </option>
                                </select>
                            </label>

                            <label class="grid gap-1 text-xs font-medium text-slate-600">
                                Value

                                <select
                                    v-model="chartMeasure"
                                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                                    @change="clearSaveFeedback"
                                >
                                    <option
                                        v-for="measure in preview.meta.measures"
                                        :key="measure"
                                        :value="measure"
                                    >
                                        {{ measureLabel(measure) }}
                                    </option>
                                </select>
                            </label>

                            <label class="grid gap-1 text-xs font-medium text-slate-600">
                                Series

                                <select
                                    v-model="chartSeries"
                                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                                    @change="clearSaveFeedback"
                                >
                                    <option :value="null">No series</option>

                                    <option
                                        v-for="dimension in chartSeriesOptions"
                                        :key="dimension"
                                        :value="dimension"
                                    >
                                        {{ dimensionLabel(dimension) }}
                                    </option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <ReportChart
                        v-if="canRenderChart"
                        class="mt-6"
                        :rows="preview.data"
                        :dimension="chartDimension"
                        :measure="chartMeasure"
                        :type="chartType"
                        :series-dimension="chartSeries"
                    />
                </section>

                <div
                    v-else-if="preview.data.length > 0"
                    class="border-b border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-600"
                >
                    Select at least one dimension and one measure to create a chart.
                </div>

                <div
                    v-if="preview.data.length === 0"
                    class="p-8 text-center text-sm text-slate-500"
                >
                    The query completed successfully, but no rows matched.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    v-for="column in previewColumns"
                                    :key="column"
                                    class="px-4 py-3 text-left font-semibold whitespace-nowrap text-slate-700"
                                >
                                    {{ column }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(row, rowIndex) in preview.data" :key="rowIndex">
                                <td
                                    v-for="column in previewColumns"
                                    :key="column"
                                    class="px-4 py-3 whitespace-nowrap text-slate-700"
                                >
                                    {{ formatCellValue(row[column]) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </section>
</template>
