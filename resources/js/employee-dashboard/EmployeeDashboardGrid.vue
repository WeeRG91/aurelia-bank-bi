<script setup lang="ts">
import { computed } from 'vue';

import EmployeeDashboardKpiCard from './EmployeeDashboardKpiCard.vue';
import type {
    EmployeeDashboard,
    EmployeeDashboardWidget,
    EmployeeDashboardWidgetResult,
} from './types';
import EmployeeDashboardTableCard from "@/employee-dashboard/EmployeeDashboardTableCard.vue";
import EmployeeDashboardChartCard from "@/employee-dashboard/EmployeeDashboardChartCard.vue";

const props = defineProps<{
    dashboard: EmployeeDashboard;
    results: EmployeeDashboardWidgetResult[];
    deletingWidgetId: number | null;
}>();

const emit = defineEmits<{
    deleteWidget: [widget: EmployeeDashboardWidget];
}>();

const widgets = computed(
    () => props.dashboard.widgets ?? [],
);

const resultsByWidget = computed(
    () =>
        new Map(
            props.results.map((result) => [
                result.widgetId,
                result,
            ]),
        ),
);

function resultFor(
    widget: EmployeeDashboardWidget,
): EmployeeDashboardWidgetResult | undefined {
    return resultsByWidget.value.get(widget.id);
}

function widgetStyle(
    widget: EmployeeDashboardWidget,
): Record<string, string> {
    return {
        '--widget-column': String(
            widget.layout.column + 1,
        ),
        '--widget-row': String(
            widget.layout.row + 1,
        ),
        '--widget-width': String(
            widget.layout.width,
        ),
        '--widget-height': String(
            widget.layout.height,
        ),
    };
}

function widgetTypeLabel(
    widget: EmployeeDashboardWidget,
): string {
    return widget.type.charAt(0).toUpperCase()
        + widget.type.slice(1);
}
</script>

<template>
    <section
        :aria-labelledby="`employee-dashboard-${dashboard.id}-heading`"
    >
        <header class="mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <h2
                    :id="`employee-dashboard-${dashboard.id}-heading`"
                    class="text-2xl font-bold text-slate-950"
                >
                    {{ dashboard.name }}
                </h2>

                <span
                    v-if="dashboard.isDefault"
                    class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800"
                >
                    Default
                </span>
            </div>

            <p
                v-if="dashboard.description !== null"
                class="mt-2 max-w-3xl text-sm leading-6 text-slate-600"
            >
                {{ dashboard.description }}
            </p>
        </header>

        <div
            v-if="widgets.length === 0"
            class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm"
        >
            <h3 class="text-lg font-semibold text-slate-900">
                This dashboard has no widgets
            </h3>

            <p class="mt-2 text-sm text-slate-600">
                Add a governed saved report as a KPI, chart, or table.
            </p>
        </div>

        <div v-else class="dashboard-grid">
            <article
                v-for="widget in widgets"
                :key="widget.id"
                class="dashboard-grid-widget group relative"
                :style="widgetStyle(widget)"
            >
                <button
                    type="button"
                    class="absolute top-3 right-3 z-10 rounded-lg border border-slate-200 bg-white/95 px-2.5 py-1.5 text-xs font-semibold text-rose-700 opacity-100 shadow-sm hover:border-rose-200 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50 lg:opacity-0 lg:group-hover:opacity-100 lg:focus:opacity-100"
                    :disabled="deletingWidgetId === widget.id"
                    :aria-label="`Delete ${widget.title}`"
                    @click="emit('deleteWidget', widget)"
                >
                    {{
                        deletingWidgetId === widget.id
                            ? 'Deleting…'
                            : 'Delete'
                    }}
                </button>

                <EmployeeDashboardKpiCard
                    v-if="
                        widget.type === 'kpi'
                        && resultFor(widget) !== undefined
                    "
                    :widget="widget"
                    :result="resultFor(widget)!"
                />

                <EmployeeDashboardChartCard
                    v-else-if="
                        widget.type === 'chart'
                        && resultFor(widget) !== undefined
                    "
                    :widget="widget"
                    :result="resultFor(widget)!"
                />

                <EmployeeDashboardTableCard
                    v-else-if="
                        widget.type === 'table'
                        && resultFor(widget) !== undefined
                    "
                    :widget="widget"
                    :result="resultFor(widget)!"
                />

                <section
                    v-else
                    class="h-full rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    :aria-labelledby="`dashboard-widget-${widget.id}-heading`"
                >
                    <header class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold tracking-wider text-amber-700 uppercase">
                                {{ widgetTypeLabel(widget) }}
                            </p>

                            <h3
                                :id="`dashboard-widget-${widget.id}-heading`"
                                class="mt-1 text-lg font-semibold text-slate-950"
                            >
                                {{ widget.title }}
                            </h3>
                        </div>

                        <span
                            v-if="resultFor(widget) !== undefined"
                            class="rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="
                                resultFor(widget)?.status === 'available'
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : 'bg-slate-100 text-slate-600'
                            "
                        >
                            {{ resultFor(widget)?.status }}
                        </span>
                    </header>

                    <div
                        v-if="resultFor(widget) === undefined"
                        class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600"
                    >
                        No runtime result was returned for this widget.
                    </div>

                    <div
                        v-else-if="resultFor(widget)?.status === 'unavailable'"
                        class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4"
                    >
                        <p class="text-sm font-semibold text-slate-700">
                            Widget unavailable
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Its saved report is missing or no longer authorized.
                        </p>
                    </div>

                    <div
                        v-else
                        class="mt-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-600"
                    >
                        {{ resultFor(widget)?.rowCount ?? 0 }}
                        governed result rows are ready for the
                        {{ widget.type }} renderer.
                    </div>
                </section>
            </article>
        </div>
    </section>
</template>

<style scoped>
.dashboard-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 1rem;
}

.dashboard-grid-widget {
    min-width: 0;
}

@media (min-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: repeat(12, minmax(0, 1fr));
        grid-auto-rows: minmax(5rem, auto);
    }

    .dashboard-grid-widget {
        grid-column:
            var(--widget-column)
            / span var(--widget-width);
        grid-row:
            var(--widget-row)
            / span var(--widget-height);
    }
}
</style>
