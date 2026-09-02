<script setup lang="ts">
import { computed, ref } from 'vue';

import type {
    DatasetSummary,
    MeasureDefinition,
    ReportBuilderBootstrap,
} from './types';

const MAX_DIMENSIONS = 20;
const MAX_MEASURES = 10;

const props = defineProps<{
    bootstrap: ReportBuilderBootstrap;
}>();

const selectedDatasetKey = ref(
    props.bootstrap.datasets[0]?.key ?? '',
);

const selectedDimensionKeys = ref<string[]>([]);
const selectedMeasureKeys = ref<string[]>([]);

const selectedDataset = computed<DatasetSummary | undefined>(
    () => props.bootstrap.datasets.find(
        (dataset) => dataset.key === selectedDatasetKey.value,
    ),
);

const requiredDimensionKeys = computed<Set<string>>(() => {
    const measures = selectedDataset.value?.measures ?? [];

    return new Set(
        measures
            .filter((measure) =>
                selectedMeasureKeys.value.includes(measure.key),
            )
            .flatMap((measure) => measure.requiredDimensions),
    );
});

function selectDataset(datasetKey: string): void {
    selectedDatasetKey.value = datasetKey;
    selectedDimensionKeys.value = [];
    selectedMeasureKeys.value = [];
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

        selectedDimensionKeys.value =
            selectedDimensionKeys.value.filter(
                (key) => key !== dimensionKey,
            );

        return;
    }

    if (selectedDimensionKeys.value.length >= MAX_DIMENSIONS) {
        return;
    }

    selectedDimensionKeys.value = [
        ...selectedDimensionKeys.value,
        dimensionKey,
    ];
}

function toggleMeasure(measure: MeasureDefinition): void {
    if (isMeasureSelected(measure.key)) {
        selectedMeasureKeys.value =
            selectedMeasureKeys.value.filter(
                (key) => key !== measure.key,
            );

        return;
    }

    if (selectedMeasureKeys.value.length >= MAX_MEASURES) {
        return;
    }

    const missingRequiredDimensions =
        measure.requiredDimensions.filter(
            (key) => ! isDimensionSelected(key),
        );

    if (
        selectedDimensionKeys.value.length
        + missingRequiredDimensions.length
        > MAX_DIMENSIONS
    ) {
        return;
    }

    selectedMeasureKeys.value = [
        ...selectedMeasureKeys.value,
        measure.key,
    ];

    selectedDimensionKeys.value = [
        ...selectedDimensionKeys.value,
        ...missingRequiredDimensions,
    ];
}
</script>

<template>
    <section
        v-if="bootstrap.datasets.length === 0"
        class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm"
    >
        <h2 class="text-lg font-semibold text-slate-950">
            No datasets available
        </h2>

        <p class="mt-2 text-sm text-slate-600">
            Your employee role currently has no active analytics datasets.
        </p>
    </section>

    <section
        v-else
        class="grid gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]"
    >
        <aside class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
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

        <div
            v-if="selectedDataset"
            class="space-y-6"
        >
            <header class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-amber-700">
                            Selected dataset
                        </p>

                        <h2 class="mt-1 text-2xl font-semibold text-slate-950">
                            {{ selectedDataset.label }}
                        </h2>
                    </div>

                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                        {{ bootstrap.reportingTimezone }}
                    </span>
                </div>

                <p class="mt-4 text-slate-600">
                    {{ selectedDataset.description }}
                </p>

                <p class="mt-3 text-sm text-slate-500">
                    Grain: {{ selectedDataset.grain }}
                </p>
            </header>

            <div class="grid gap-6 xl:grid-cols-2">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-950">
                            Dimensions
                        </h3>

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
                                    isRequiredDimension(dimension.key)
                                    || (
                                        !isDimensionSelected(dimension.key)
                                        && selectedDimensionKeys.length >= MAX_DIMENSIONS
                                    )
                                "
                                @change="toggleDimension(dimension.key)"
                            >

                            <span>
                                <span class="block text-sm font-medium text-slate-900">
                                    {{ dimension.label }}
                                </span>

                                <span class="mt-1 block text-xs text-slate-500">
                                    {{ dimension.kind }} · {{ dimension.dataType }}
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
                        <h3 class="font-semibold text-slate-950">
                            Measures
                        </h3>

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
                                    !isMeasureSelected(measure.key)
                                    && selectedMeasureKeys.length >= MAX_MEASURES
                                "
                                @change="toggleMeasure(measure)"
                            >

                            <span>
                                <span class="block text-sm font-medium text-slate-900">
                                    {{ measure.label }}
                                </span>

                                <span class="mt-1 block text-xs text-slate-500">
                                    {{ measure.aggregation }} · {{ measure.dataType }}
                                </span>
                            </span>
                        </label>
                    </div>
                </section>
            </div>

            <footer class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">
                    Report definition
                </h3>

                <p class="mt-2 text-sm text-slate-600">
                    {{ selectedDimensionKeys.length }} dimensions and
                    {{ selectedMeasureKeys.length }} measures selected.
                </p>

                <button
                    type="button"
                    class="mt-4 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="
                        selectedDimensionKeys.length === 0
                        && selectedMeasureKeys.length === 0
                    "
                >
                    Preview report
                </button>
            </footer>
        </div>
    </section>
</template>
