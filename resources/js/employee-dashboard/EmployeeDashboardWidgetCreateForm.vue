<script setup lang="ts">
import { computed, ref } from 'vue';

import type {
    EmployeeDashboard,
    EmployeeDashboardSavedReportOption,
    EmployeeDashboardWidget,
    EmployeeDashboardWidgetResponse,
    EmployeeDashboardWidgetType,
} from './types';

const props = defineProps<{
    dashboard: EmployeeDashboard;
    savedReports: EmployeeDashboardSavedReportOption[];
}>();

const emit = defineEmits<{
    created: [widget: EmployeeDashboardWidget];
}>();

const savedReportId = ref<number | null>(null);
const title = ref('');
const widgetType = ref<EmployeeDashboardWidgetType>('kpi');
const gridColumn = ref(0);
const gridRow = ref(0);
const gridWidth = ref(4);
const gridHeight = ref(3);
const showComparison = ref(false);
const rowLimit = ref(25);
const saving = ref(false);
const error = ref<string | null>(null);

const hasReports = computed(
    () => props.savedReports.length > 0,
);

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    )?.content ?? '';
}

function configuration(): Record<string, boolean | number> | null {
    if (widgetType.value === 'kpi') {
        return {
            show_comparison: showComparison.value,
        };
    }

    if (widgetType.value === 'table') {
        return {
            row_limit: rowLimit.value,
        };
    }

    return null;
}

async function submit(): Promise<void> {
    if (savedReportId.value === null) {
        error.value = 'Select a saved report.';

        return;
    }

    saving.value = true;
    error.value = null;

    try {
        const response = await fetch(
            props.dashboard.widgetStoreUrl,
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    saved_report_id: savedReportId.value,
                    title: title.value,
                    widget_type: widgetType.value,
                    grid_column: gridColumn.value,
                    grid_row: gridRow.value,
                    grid_width: gridWidth.value,
                    grid_height: gridHeight.value,
                    configuration: configuration(),
                }),
            },
        );

        const payload = await response.json() as
            | EmployeeDashboardWidgetResponse
            | { message?: string };

        if (!response.ok) {
            throw new Error(
                'message' in payload
                && typeof payload.message === 'string'
                    ? payload.message
                    : 'The widget could not be created.',
            );
        }

        const created = (
            payload as EmployeeDashboardWidgetResponse
        ).data;

        savedReportId.value = null;
        title.value = '';
        showComparison.value = false;

        emit('created', created);
    } catch (exception) {
        error.value = exception instanceof Error
            ? exception.message
            : 'The widget could not be created.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <details class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <summary class="cursor-pointer px-5 py-4 font-semibold text-slate-900">
            Add a widget to {{ dashboard.name }}
        </summary>

        <div
            v-if="!hasReports"
            class="border-t border-slate-200 p-5 text-sm text-slate-600"
        >
            No authorized saved reports are available. Create a compatible
            saved report before adding a widget.
        </div>

        <form
            v-else
            class="border-t border-slate-200 p-5"
            @submit.prevent="submit"
        >
            <div class="grid gap-4 md:grid-cols-3">
                <label class="text-sm font-semibold text-slate-700">
                    Saved report

                    <select
                        v-model="savedReportId"
                        required
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal shadow-sm"
                    >
                        <option :value="null" disabled>
                            Select a report
                        </option>

                        <option
                            v-for="report in savedReports"
                            :key="report.id"
                            :value="report.id"
                        >
                            {{ report.name }} · {{ report.dataset }}
                        </option>
                    </select>
                </label>

                <label class="text-sm font-semibold text-slate-700">
                    Widget title

                    <input
                        v-model="title"
                        required
                        maxlength="150"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal shadow-sm"
                    >
                </label>

                <label class="text-sm font-semibold text-slate-700">
                    Widget type

                    <select
                        v-model="widgetType"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal shadow-sm"
                    >
                        <option value="kpi">KPI</option>
                        <option value="chart">Chart</option>
                        <option value="table">Table</option>
                    </select>
                </label>
            </div>

            <fieldset class="mt-5">
                <legend class="text-sm font-semibold text-slate-800">
                    Twelve-column layout
                </legend>

                <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <label class="text-sm text-slate-700">
                        Column (0–11)

                        <input
                            v-model.number="gridColumn"
                            type="number"
                            min="0"
                            max="11"
                            required
                            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2"
                        >
                    </label>

                    <label class="text-sm text-slate-700">
                        Row

                        <input
                            v-model.number="gridRow"
                            type="number"
                            min="0"
                            required
                            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2"
                        >
                    </label>

                    <label class="text-sm text-slate-700">
                        Width (1–12)

                        <input
                            v-model.number="gridWidth"
                            type="number"
                            min="1"
                            max="12"
                            required
                            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2"
                        >
                    </label>

                    <label class="text-sm text-slate-700">
                        Height (1–12)

                        <input
                            v-model.number="gridHeight"
                            type="number"
                            min="1"
                            max="12"
                            required
                            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2"
                        >
                    </label>
                </div>
            </fieldset>

            <label
                v-if="widgetType === 'kpi'"
                class="mt-5 flex items-center gap-2 text-sm text-slate-700"
            >
                <input
                    v-model="showComparison"
                    type="checkbox"
                    class="rounded border-slate-300 text-amber-700 focus:ring-amber-500"
                >

                Compare with the previous reporting period
            </label>

            <label
                v-if="widgetType === 'table'"
                class="mt-5 block max-w-xs text-sm font-semibold text-slate-700"
            >
                Maximum rows

                <input
                    v-model.number="rowLimit"
                    type="number"
                    min="1"
                    max="100"
                    required
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal"
                >
            </label>

            <p
                v-if="error !== null"
                role="alert"
                class="mt-4 text-sm font-medium text-rose-700"
            >
                {{ error }}
            </p>

            <button
                type="submit"
                :disabled="saving"
                class="mt-5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ saving ? 'Adding…' : 'Add widget' }}
            </button>
        </form>
    </details>
</template>
