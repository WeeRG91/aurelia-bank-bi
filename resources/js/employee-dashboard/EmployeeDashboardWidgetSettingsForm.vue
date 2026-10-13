<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import type {
    EmployeeDashboard,
    EmployeeDashboardWidget,
    EmployeeDashboardWidgetResponse,
} from './types';

const props = defineProps<{
    dashboard: EmployeeDashboard;
}>();

const emit = defineEmits<{
    updated: [widget: EmployeeDashboardWidget];
}>();

const selectedWidgetId = ref<number | null>(null);
const title = ref('');
const gridColumn = ref(0);
const gridRow = ref(0);
const gridWidth = ref(4);
const gridHeight = ref(3);
const saving = ref(false);
const error = ref<string | null>(null);
const success = ref<string | null>(null);

const widgets = computed(
    () => props.dashboard.widgets ?? [],
);

const selectedWidget = computed(
    () =>
        widgets.value.find(
            (widget) =>
                widget.id === selectedWidgetId.value,
        ) ?? null,
);

watch(
    () => props.dashboard.id,
    () => {
        selectedWidgetId.value =
            widgets.value[0]?.id ?? null;
    },
    {
        immediate: true,
    },
);

watch(
    selectedWidget,
    (widget) => {
        error.value = null;
        success.value = null;

        if (widget === null) {
            title.value = '';

            return;
        }

        title.value = widget.title;
        gridColumn.value = widget.layout.column;
        gridRow.value = widget.layout.row;
        gridWidth.value = widget.layout.width;
        gridHeight.value = widget.layout.height;
    },
    {
        immediate: true,
    },
);

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    )?.content ?? '';
}

async function submit(): Promise<void> {
    const widget = selectedWidget.value;

    if (widget === null) {
        error.value = 'Select a widget.';

        return;
    }

    saving.value = true;
    error.value = null;
    success.value = null;

    try {
        const response = await fetch(widget.updateUrl, {
            method: 'PUT',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                saved_report_id: widget.savedReportId,
                title: title.value,
                widget_type: widget.type,
                grid_column: gridColumn.value,
                grid_row: gridRow.value,
                grid_width: gridWidth.value,
                grid_height: gridHeight.value,
                configuration: widget.configuration,
            }),
        });

        const payload = await response.json() as
            | EmployeeDashboardWidgetResponse
            | { message?: string };

        if (!response.ok) {
            throw new Error(
                'message' in payload
                && typeof payload.message === 'string'
                    ? payload.message
                    : 'The widget could not be updated.',
            );
        }

        const updated = (
            payload as EmployeeDashboardWidgetResponse
        ).data;

        success.value = 'Widget settings updated.';

        emit('updated', updated);
    } catch (exception) {
        error.value = exception instanceof Error
            ? exception.message
            : 'The widget could not be updated.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <details
        v-if="widgets.length > 0"
        class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm"
    >
        <summary class="cursor-pointer px-5 py-4 font-semibold text-slate-900">
            Edit widget layout
        </summary>

        <form
            class="border-t border-slate-200 p-5"
            @submit.prevent="submit"
        >
            <div class="grid gap-4 md:grid-cols-2">
                <label class="text-sm font-semibold text-slate-700">
                    Widget

                    <select
                        v-model="selectedWidgetId"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal shadow-sm"
                    >
                        <option
                            v-for="widget in widgets"
                            :key="widget.id"
                            :value="widget.id"
                        >
                            {{ widget.title }} · {{ widget.type }}
                        </option>
                    </select>
                </label>

                <label class="text-sm font-semibold text-slate-700">
                    Title

                    <input
                        v-model="title"
                        required
                        maxlength="150"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal shadow-sm"
                    >
                </label>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label class="text-sm font-semibold text-slate-700">
                    Column

                    <input
                        v-model.number="gridColumn"
                        type="number"
                        min="0"
                        max="11"
                        required
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal shadow-sm"
                    >
                </label>

                <label class="text-sm font-semibold text-slate-700">
                    Row

                    <input
                        v-model.number="gridRow"
                        type="number"
                        min="0"
                        required
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal shadow-sm"
                    >
                </label>

                <label class="text-sm font-semibold text-slate-700">
                    Width

                    <input
                        v-model.number="gridWidth"
                        type="number"
                        min="1"
                        max="12"
                        required
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal shadow-sm"
                    >
                </label>

                <label class="text-sm font-semibold text-slate-700">
                    Height

                    <input
                        v-model.number="gridHeight"
                        type="number"
                        min="1"
                        max="12"
                        required
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-normal shadow-sm"
                    >
                </label>
            </div>

            <p class="mt-3 text-xs text-slate-500">
                Column plus width cannot exceed twelve. Overlapping widgets
                are currently allowed.
            </p>

            <p
                v-if="error !== null"
                role="alert"
                class="mt-4 text-sm font-medium text-rose-700"
            >
                {{ error }}
            </p>

            <p
                v-if="success !== null"
                role="status"
                class="mt-4 text-sm font-medium text-emerald-700"
            >
                {{ success }}
            </p>

            <button
                type="submit"
                class="mt-5 rounded-lg bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="saving || selectedWidget === null"
            >
                {{ saving ? 'Saving…' : 'Save widget layout' }}
            </button>
        </form>
    </details>
</template>
