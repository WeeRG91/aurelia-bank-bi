<script setup lang="ts">
import { computed, ref } from 'vue';

import type {
    DatasetSummary,
    ReportBuilderBootstrap,
} from './types';

const props = defineProps<{
    bootstrap: ReportBuilderBootstrap;
}>();

const selectedDatasetKey = ref(
    props.bootstrap.datasets[0]?.key ?? '',
);

const selectedDataset = computed<DatasetSummary | undefined>(
    () => props.bootstrap.datasets.find(
        (dataset) => dataset.key === selectedDatasetKey.value,
    ),
);
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
                    @click="selectedDatasetKey = dataset.key"
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
            class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
        >
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

            <p class="mt-5 text-slate-600">
                {{ selectedDataset.description }}
            </p>

            <dl class="mt-6 border-t border-slate-200 pt-5">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Dataset grain
                </dt>

                <dd class="mt-2 text-sm text-slate-800">
                    {{ selectedDataset.grain }}
                </dd>
            </dl>

            <div class="mt-8 rounded-lg bg-slate-50 p-4">
                <p class="text-sm text-slate-600">
                    Dimension and measure selection will be added in the next slice.
                </p>
            </div>
        </div>
    </section>
</template>
