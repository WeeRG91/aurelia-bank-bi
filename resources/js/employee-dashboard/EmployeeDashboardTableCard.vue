<script setup lang="ts">
import { computed } from 'vue';

import type {
    DashboardResultRow,
    EmployeeDashboardWidget,
    EmployeeDashboardWidgetResult,
} from './types';

const props = defineProps<{
    widget: EmployeeDashboardWidget;
    result: EmployeeDashboardWidgetResult;
}>();

const columns = computed<string[]>(() => {
    const selectedFields = [
        ...props.result.dimensions,
        ...props.result.measures,
    ];

    return [...new Set(selectedFields)];
});

function columnLabel(column: string): string {
    return column
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) =>
            character.toUpperCase(),
        );
}

function cellValue(
    row: DashboardResultRow,
    column: string,
): string {
    const value = row[column];

    if (value === null || value === undefined) {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    return String(value);
}
</script>

<template>
    <section
        class="h-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        :aria-labelledby="`dashboard-widget-${widget.id}-heading`"
    >
        <header class="border-b border-slate-200 px-5 py-4">
            <p class="text-xs font-semibold tracking-wider text-amber-700 uppercase">
                Table
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
            v-else-if="result.rows.length === 0"
            class="m-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-500"
        >
            No table rows were returned for the selected period.
        </div>

        <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                <tr>
                    <th
                        v-for="column in columns"
                        :key="column"
                        scope="col"
                        class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold tracking-wide text-slate-600 uppercase"
                    >
                        {{ columnLabel(column) }}
                    </th>
                </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                <tr
                    v-for="(row, rowIndex) in result.rows"
                    :key="rowIndex"
                    class="hover:bg-slate-50"
                >
                    <td
                        v-for="column in columns"
                        :key="column"
                        class="whitespace-nowrap px-5 py-3 text-sm text-slate-700"
                    >
                        {{ cellValue(row, column) }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
