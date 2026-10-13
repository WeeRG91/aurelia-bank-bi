<script setup lang="ts">
import { computed } from 'vue';

import type {
    EmployeeDashboardKpiComparison,
    EmployeeDashboardWidget,
    EmployeeDashboardWidgetResult,
} from './types';

const props = defineProps<{
    widget: EmployeeDashboardWidget;
    result: EmployeeDashboardWidgetResult;
}>();

const cards = computed(() =>
    props.result.values.map((value, index) => ({
        value,
        comparison: props.result.comparisons[index],
    })),
);

function formatValue(
    value: number | string | null,
    context: Record<string, boolean | number | string | null>,
): string {
    if (value === null) {
        return 'Unavailable';
    }

    const numericValue =
        typeof value === 'number'
            ? value
            : Number(value);

    if (!Number.isFinite(numericValue)) {
        return String(value);
    }

    const measure = props.result.measures[0] ?? '';

    if (measure.includes('count')) {
        return new Intl.NumberFormat(undefined, {
            maximumFractionDigits: 0,
        }).format(numericValue);
    }

    const currency = context.currency;

    if (typeof currency === 'string') {
        try {
            return new Intl.NumberFormat(undefined, {
                style: 'currency',
                currency,
                maximumFractionDigits: 2,
            }).format(numericValue);
        } catch {
            // Fall through to governed numeric formatting.
        }
    }

    return new Intl.NumberFormat(undefined, {
        maximumFractionDigits: 2,
    }).format(numericValue);
}

function contextLabel(
    context: Record<string, boolean | number | string | null>,
): string {
    const entries = Object.entries(context);

    if (entries.length === 0) {
        return 'All authorized data';
    }

    return entries
        .map(([key, value]) => {
            const label = key.replaceAll('_', ' ');

            return `${label}: ${String(value ?? 'Unavailable')}`;
        })
        .join(' · ');
}

function comparisonLabel(
    comparison: EmployeeDashboardKpiComparison | undefined,
): string {
    if (comparison === undefined) {
        return '';
    }

    if (
        comparison.state === 'comparable'
        && comparison.percentage_change !== null
    ) {
        const percentage = Number(
            comparison.percentage_change,
        );

        const prefix = percentage > 0 ? '+' : '';

        return `${prefix}${comparison.percentage_change}% vs previous period`;
    }

    if (comparison.state === 'previous_zero') {
        return 'Previous period was zero';
    }

    if (comparison.state === 'missing_previous') {
        return 'No previous-period value';
    }

    return 'Current value unavailable';
}

function comparisonClass(
    comparison: EmployeeDashboardKpiComparison | undefined,
): string {
    if (
        comparison === undefined
        || comparison.state !== 'comparable'
        || comparison.percentage_change === null
    ) {
        return 'bg-slate-100 text-slate-600';
    }

    const percentage = Number(
        comparison.percentage_change,
    );

    if (percentage > 0) {
        return 'bg-sky-50 text-sky-700';
    }

    if (percentage < 0) {
        return 'bg-violet-50 text-violet-700';
    }

    return 'bg-slate-100 text-slate-700';
}

function trendSymbol(
    comparison: EmployeeDashboardKpiComparison | undefined,
): string {
    if (
        comparison === undefined
        || comparison.state !== 'comparable'
        || comparison.percentage_change === null
    ) {
        return '—';
    }

    const percentage = Number(
        comparison.percentage_change,
    );

    if (percentage > 0) {
        return '↑';
    }

    if (percentage < 0) {
        return '↓';
    }

    return '→';
}
</script>

<template>
    <section
        class="h-full rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
        :aria-labelledby="`dashboard-widget-${widget.id}-heading`"
    >
        <header>
            <p class="text-xs font-semibold tracking-wider text-amber-700 uppercase">
                KPI
            </p>

            <h3
                :id="`dashboard-widget-${widget.id}-heading`"
                class="mt-1 text-lg font-semibold text-slate-950"
            >
                {{ widget.title }}
            </h3>
        </header>

        <div
            v-if="result.status === 'unavailable'"
            class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4"
        >
            <p class="text-sm font-semibold text-slate-700">
                Widget unavailable
            </p>

            <p class="mt-1 text-sm text-slate-500">
                Its saved report is missing or is no longer authorized.
            </p>
        </div>

        <div
            v-else-if="cards.length === 0"
            class="mt-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-500"
        >
            No KPI value was returned for the selected period.
        </div>

        <div v-else class="mt-5 grid gap-4">
            <article
                v-for="(card, index) in cards"
                :key="`${widget.id}-${index}`"
                class="rounded-xl border border-slate-200 bg-slate-50 p-4"
            >
                <p class="text-xs font-medium text-slate-500 capitalize">
                    {{ contextLabel(card.value.context) }}
                </p>

                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                    {{
                        formatValue(
                            card.value.value,
                            card.value.context,
                        )
                    }}
                </p>

                <p
                    v-if="card.comparison !== undefined"
                    class="mt-3 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                    :class="comparisonClass(card.comparison)"
                >
                    <span aria-hidden="true">
                        {{ trendSymbol(card.comparison) }}
                    </span>

                    {{ comparisonLabel(card.comparison) }}
                </p>
            </article>
        </div>
    </section>
</template>
