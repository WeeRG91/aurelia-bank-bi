<script setup lang="ts">
import { computed } from 'vue';

import ReportChart from '../report-builder/ReportChart.vue';
import type {
    DashboardBootstrap,
    DashboardRow,
    DashboardWidgetKey,
    DashboardWidgetPayload,
} from './types';

const props = defineProps<{
    bootstrap: DashboardBootstrap;
}>();

const summaryMetrics = [
    {
        key: 'transaction_count',
        label: 'Transactions',
        color: 'text-slate-950',
    },
    {
        key: 'incoming_amount',
        label: 'Incoming',
        color: 'text-emerald-700',
    },
    {
        key: 'outgoing_amount',
        label: 'Outgoing',
        color: 'text-rose-700',
    },
    {
        key: 'net_cash_flow',
        label: 'Net cash flow',
        color: 'text-amber-700',
    },
] as const;

function widget(key: DashboardWidgetKey): DashboardWidgetPayload | undefined {
    return props.bootstrap.widgets.find((candidate) => candidate.key === key);
}

const summary = computed(() => widget('transaction_summary'));
const dailyCashFlow = computed(() => widget('daily_cash_flow'));
const transactionMix = computed(() => widget('transaction_mix'));

const summaryRows = computed<DashboardRow[]>(() =>
    [...(summary.value?.rows ?? [])].sort((left, right) =>
        String(left.currency ?? '').localeCompare(String(right.currency ?? '')),
    ),
);

const dailyRows = computed<DashboardRow[]>(() =>
    [...(dailyCashFlow.value?.rows ?? [])].sort((left, right) => {
        const dateComparison = String(left.booking_date ?? '').localeCompare(
            String(right.booking_date ?? ''),
        );

        if (dateComparison !== 0) {
            return dateComparison;
        }

        return String(left.currency ?? '').localeCompare(String(right.currency ?? ''));
    }),
);

const transactionMixRows = computed<DashboardRow[]>(() =>
    [...(transactionMix.value?.rows ?? [])].sort(
        (left, right) =>
            numericValue(right.transaction_count) - numericValue(left.transaction_count),
    ),
);

function changePeriod(event: Event): void {
    const target = event.target;

    if (!(target instanceof HTMLSelectElement)) {
        return;
    }

    const url = new URL(props.bootstrap.dashboardUrl, window.location.origin);

    url.searchParams.set('period', target.value);
    window.location.assign(url);
}

function numericValue(value: unknown): number {
    const parsed =
        typeof value === 'number'
            ? value
            : typeof value === 'string' && value.trim() !== ''
              ? Number(value)
              : 0;

    return Number.isFinite(parsed) ? parsed : 0;
}

function formatMetric(
    value: unknown,
    key: (typeof summaryMetrics)[number]['key'],
    currency: string,
): string {
    const numeric = numericValue(value);

    if (key === 'transaction_count') {
        return new Intl.NumberFormat().format(numeric);
    }

    try {
        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency,
            maximumFractionDigits: 2,
        }).format(numeric);
    } catch {
        return `${new Intl.NumberFormat(undefined, {
            maximumFractionDigits: 2,
        }).format(numeric)} ${currency}`;
    }
}

function periodLabel(period: string | undefined): string {
    return period?.replaceAll('_', ' ') ?? 'current period';
}
</script>

<template>
    <section aria-labelledby="analytics-overview-heading">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold tracking-wider text-amber-700 uppercase">
                    Governed analytics
                </p>

                <h2 id="analytics-overview-heading" class="mt-2 text-2xl font-bold text-slate-950">
                    Transaction overview
                </h2>

                <p class="mt-2 max-w-3xl text-sm text-slate-600">
                    Role-scoped activity calculated through the governed analytics layer.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:items-end">
                <label class="text-sm font-semibold text-slate-700">
                    Reporting period

                    <select
                        :value="bootstrap.selectedPeriod"
                        class="mt-1 block min-w-48 rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-200 focus:outline-none"
                        @change="changePeriod"
                    >
                        <option
                            v-for="period in bootstrap.periods"
                            :key="period.value"
                            :value="period.value"
                        >
                            {{ period.label }}
                        </option>
                    </select>
                </label>

                <p class="text-xs text-slate-500">
                    Reporting timezone:
                    <span class="font-semibold text-slate-700">
                        {{ bootstrap.reportingTimezone }}
                    </span>
                </p>
            </div>
        </div>

        <div
            v-if="bootstrap.widgets.length === 0"
            class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm"
        >
            <h3 class="text-lg font-semibold text-slate-900">No analytics widgets available</h3>

            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600">
                Your role does not currently include access to business datasets. Governance and
                platform administration remain available from the navigation.
            </p>
        </div>

        <template v-else>
            <section
                v-if="summary !== undefined"
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">
                            {{ summary.label }}
                        </h3>

                        <p class="mt-1 text-sm text-slate-600">
                            {{ summary.description }}
                        </p>
                    </div>

                    <span
                        class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 capitalize"
                    >
                        {{ periodLabel(summary.period) }}
                    </span>
                </div>

                <div v-if="summaryRows.length > 0" class="mt-6 space-y-5">
                    <article v-for="row in summaryRows" :key="String(row.currency ?? 'unknown')">
                        <h4 class="text-sm font-bold tracking-wide text-slate-700">
                            {{ String(row.currency ?? 'Unknown currency') }}
                        </h4>

                        <dl class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div
                                v-for="metric in summaryMetrics"
                                :key="metric.key"
                                class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4"
                            >
                                <dt
                                    class="text-xs font-semibold tracking-wide text-slate-500 uppercase"
                                >
                                    {{ metric.label }}
                                </dt>

                                <dd class="mt-2 text-xl font-bold" :class="metric.color">
                                    {{
                                        formatMetric(
                                            row[metric.key],
                                            metric.key,
                                            String(row.currency ?? ''),
                                        )
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </article>
                </div>

                <p
                    v-else
                    class="mt-6 rounded-xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500"
                >
                    No transaction activity was found for this reporting period.
                </p>
            </section>

            <div class="mt-6 grid gap-6 xl:grid-cols-2">
                <section
                    v-if="dailyCashFlow !== undefined"
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <h3 class="text-lg font-semibold text-slate-950">
                        {{ dailyCashFlow.label }}
                    </h3>

                    <p class="mt-1 text-sm text-slate-600">
                        {{ dailyCashFlow.description }}
                    </p>

                    <ReportChart
                        v-if="dailyRows.length > 0"
                        class="mt-5"
                        :rows="dailyRows"
                        dimension="booking_date"
                        measure="net_cash_flow"
                        series-dimension="currency"
                        type="line"
                    />

                    <p
                        v-else
                        class="mt-6 rounded-xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500"
                    >
                        No daily cash-flow data is available.
                    </p>
                </section>

                <section
                    v-if="transactionMix !== undefined"
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <h3 class="text-lg font-semibold text-slate-950">
                        {{ transactionMix.label }}
                    </h3>

                    <p class="mt-1 text-sm text-slate-600">
                        {{ transactionMix.description }}
                    </p>

                    <ReportChart
                        v-if="transactionMixRows.length > 0"
                        class="mt-5"
                        :rows="transactionMixRows"
                        dimension="transaction_type"
                        measure="transaction_count"
                        :series-dimension="null"
                        type="bar"
                    />

                    <p
                        v-else
                        class="mt-6 rounded-xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500"
                    >
                        No transaction-type data is available.
                    </p>
                </section>
            </div>
        </template>
    </section>
</template>
