<script setup lang="ts">
import { computed, ref } from 'vue';

import type {
    DatasetSummary,
    MeasureDefinition,
    ReportBuilderBootstrap,
    ReportPreviewResponse,
    ValidationErrorResponse,
} from './types';
import { axios } from '../bootstrap';

const MAX_DIMENSIONS = 20;
const MAX_MEASURES = 10;

const props = defineProps<{
    bootstrap: ReportBuilderBootstrap;
}>();

const selectedDatasetKey = ref(props.bootstrap.datasets[0]?.key ?? '');

const selectedDimensionKeys = ref<string[]>([]);
const selectedMeasureKeys = ref<string[]>([]);

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

            <footer class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Report definition</h3>

                <p class="mt-2 text-sm text-slate-600">
                    {{ selectedDimensionKeys.length }} dimensions and
                    {{ selectedMeasureKeys.length }} measures selected.
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
