<script setup lang="ts">
import { computed, ref } from 'vue';

import type {
    DatasetSummary,
    DimensionDefinition,
    FilterOperator,
    MeasureDefinition,
    ReportBuilderBootstrap,
    ReportFilterDraft,
    ReportFilterPayload,
    ReportPreviewResponse,
    ValidationErrorResponse,
} from './types';
import { axios } from '../bootstrap';

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

const props = defineProps<{
    bootstrap: ReportBuilderBootstrap;
}>();

const selectedDatasetKey = ref(props.bootstrap.datasets[0]?.key ?? '');

const selectedDimensionKeys = ref<string[]>([]);
const selectedMeasureKeys = ref<string[]>([]);
const selectedFilters = ref<ReportFilterDraft[]>([]);
let nextFilterId = 1;

const isPreviewLoading = ref<boolean>(false);
const preview = ref<ReportPreviewResponse | null>(null);
const previewError = ref<string | null>(null);

const previewColumns = computed<string[]>(() => {
    if (preview.value === null) {
        return [];
    }

    return [...preview.value.meta.dimensions, ...preview.value.meta.measures];
});

const selectedDataset = computed<DatasetSummary | undefined>(() =>
    props.bootstrap.datasets.find((dataset) => dataset.key === selectedDatasetKey.value),
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
}

function dimensionForFilter(filter: ReportFilterDraft): DimensionDefinition | undefined {
    return selectedDataset.value?.dimensions.find(
        (dimension) => dimension.key === filter.dimension,
    );
}

function addFilter(): void {
    const dimension = selectedDataset.value?.dimensions[0];
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

async function previewReport(): Promise<void> {
    if (selectedDataset.value === undefined) {
        return;
    }

    isPreviewLoading.value = true;
    previewError.value = null;
    preview.value = null;

    try {
        const response = await axios.post<ReportPreviewResponse>(props.bootstrap.previewUrl, {
            dataset: selectedDataset.value.key,
            dimensions: selectedDimensionKeys.value,
            measures: selectedMeasureKeys.value,
            limit: 100,
        });

        preview.value = response.data;
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

                <button
                    type="button"
                    class="mt-4 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="
                        isPreviewLoading ||
                        (selectedDimensionKeys.length === 0 && selectedMeasureKeys.length === 0)
                    "
                    @click="previewReport"
                >
                    {{ isPreviewLoading ? 'Generating preview…' : 'Preview report' }}
                </button>
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
