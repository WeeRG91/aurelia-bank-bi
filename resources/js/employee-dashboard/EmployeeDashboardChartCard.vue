<script setup lang="ts">
import ReportChart from '@/report-builder/ReportChart.vue';

import type {
    EmployeeDashboardWidget,
    EmployeeDashboardWidgetResult,
} from './types';

defineProps<{
    widget: EmployeeDashboardWidget;
    result: EmployeeDashboardWidgetResult;
}>();
</script>

<template>
    <section
        class="h-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        :aria-labelledby="`dashboard-widget-${widget.id}-heading`"
    >
        <header class="border-b border-slate-200 px-5 py-4">
            <p class="text-xs font-semibold tracking-wider text-amber-700 uppercase">
                Chart
            </p>

            <h3
                :id="`dashboard-widget-${widget.id}-heading`"
                class="mt-1 text-lg font-semibold text-slate-950"
            >
                {{ widget.title }}
            </h3>

            <p
                v-if="result.status === 'available'"
                class="mt-1 text-sm text-slate-500"
            >
                {{ result.rowCount }}
                governed result
                {{ result.rowCount === 1 ? 'row' : 'rows' }}
            </p>
        </header>

        <div
            v-if="result.status === 'unavailable'"
            class="m-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4"
        >
            <p class="text-sm font-semibold text-slate-700">
                Widget unavailable
            </p>

            <p class="mt-1 text-sm text-slate-500">
                Its saved report is missing or no longer authorized.
            </p>
        </div>

        <div
            v-else-if="result.visualization === null"
            class="m-5 rounded-xl border border-dashed border-amber-300 bg-amber-50 p-4"
        >
            <p class="text-sm font-semibold text-amber-900">
                Chart configuration unavailable
            </p>

            <p class="mt-1 text-sm text-amber-800">
                Configure and save a visualization in the referenced report.
            </p>
        </div>

        <div
            v-else-if="result.rows.length === 0"
            class="m-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-500"
        >
            No chart data was returned for the selected period.
        </div>

        <div v-else class="p-5">
            <ReportChart
                :rows="result.rows"
                :dimension="result.visualization.dimension"
                :measure="result.visualization.measure"
                :series-dimension="result.visualization.series"
                :type="result.visualization.type"
            />
        </div>
    </section>
</template>
