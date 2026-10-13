<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

import EmployeeDashboardGrid from './EmployeeDashboardGrid.vue';
import type {
    EmployeeDashboard,
    EmployeeDashboardBootstrap,
    EmployeeDashboardCollectionResponse,
    EmployeeDashboardResponse,
    EmployeeDashboardResultsMeta,
    EmployeeDashboardResultsResponse, EmployeeDashboardWidget,
    EmployeeDashboardWidgetResult,
} from './types';
import EmployeeDashboardCreateForm from "@/employee-dashboard/EmployeeDashboardCreateForm.vue";
import EmployeeDashboardWidgetCreateForm from "@/employee-dashboard/EmployeeDashboardWidgetCreateForm.vue";
import EmployeeDashboardSettingsForm from "@/employee-dashboard/EmployeeDashboardSettingsForm.vue";
import EmployeeDashboardWidgetSettingsForm from "@/employee-dashboard/EmployeeDashboardWidgetSettingsForm.vue";

const props = defineProps<{
    bootstrap: EmployeeDashboardBootstrap;
}>();

const dashboards = ref<EmployeeDashboard[]>([]);
const dashboard = ref<EmployeeDashboard | null>(null);
const results = ref<EmployeeDashboardWidgetResult[]>([]);
const resultsMeta = ref<EmployeeDashboardResultsMeta | null>(null);
const selectedDashboardId = ref<number | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const deletingWidgetId = ref<number | null>(null);

let requestSequence = 0;

const generatedAtLabel = computed(() => {
    if (resultsMeta.value === null) {
        return null;
    }

    const generatedAt = new Date(
        resultsMeta.value.generatedAt,
    );

    if (Number.isNaN(generatedAt.getTime())) {
        return 'Generation time unavailable';
    }

    try {
        return new Intl.DateTimeFormat(undefined, {
            dateStyle: 'medium',
            timeStyle: 'medium',
            timeZone: resultsMeta.value.reportingTimezone,
        }).format(generatedAt);
    } catch {
        return generatedAt.toLocaleString();
    }
});

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    )?.content ?? '';
}

async function deleteWidget(
    widget: EmployeeDashboardWidget,
): Promise<void> {
    const confirmed = window.confirm(
        `Delete the widget “${widget.title}”?`,
    );

    if (!confirmed) {
        return;
    }

    deletingWidgetId.value = widget.id;
    error.value = null;

    try {
        const response = await fetch(widget.destroyUrl, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        });

        if (!response.ok) {
            const payload = await response.json()
                .catch(() => null) as {
                message?: string;
            } | null;

            throw new Error(
                payload?.message
                ?? `Widget deletion failed with status ${response.status}.`,
            );
        }

        if (selectedDashboardId.value !== null) {
            await loadDashboard(
                selectedDashboardId.value,
            );
        }
    } catch (exception) {
        error.value = exception instanceof Error
            ? exception.message
            : 'The widget could not be deleted.';
    } finally {
        deletingWidgetId.value = null;
    }
}

async function fetchJson<T>(url: string): Promise<T> {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(
            `Dashboard request failed with status ${response.status}.`,
        );
    }

    return await response.json() as T;
}

async function loadDashboard(
    dashboardId: number,
): Promise<void> {
    const summary = dashboards.value.find(
        (candidate) => candidate.id === dashboardId,
    );

    if (summary === undefined) {
        error.value = 'The selected dashboard is unavailable.';

        return;
    }

    const requestId = ++requestSequence;

    selectedDashboardId.value = dashboardId;
    loading.value = true;
    error.value = null;

    try {
        const [dashboardResponse, resultsResponse] =
            await Promise.all([
                fetchJson<EmployeeDashboardResponse>(
                    summary.showUrl,
                ),
                fetchJson<EmployeeDashboardResultsResponse>(
                    summary.resultsUrl,
                ),
            ]);

        if (requestId !== requestSequence) {
            return;
        }

        dashboard.value = dashboardResponse.data;
        results.value = resultsResponse.data;
        resultsMeta.value = resultsResponse.meta;
    } catch (exception) {
        if (requestId !== requestSequence) {
            return;
        }

        dashboard.value = null;
        results.value = [];
        resultsMeta.value = null;

        error.value = exception instanceof Error
            ? exception.message
            : 'The dashboard could not be loaded.';
    } finally {
        if (requestId === requestSequence) {
            loading.value = false;
        }
    }
}

async function loadDashboards(
    preferredDashboardId?: number,
): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
        const response =
            await fetchJson<EmployeeDashboardCollectionResponse>(
                props.bootstrap.indexUrl,
            );

        dashboards.value = response.data;

        const initialDashboard =
            response.data.find(
                (candidate) =>
                    candidate.id === preferredDashboardId,
            )
            ?? response.data.find(
                (candidate) => candidate.isDefault,
            )
            ?? response.data[0];

        if (initialDashboard === undefined) {
            dashboard.value = null;
            results.value = [];
            resultsMeta.value = null;
            loading.value = false;

            return;
        }

        await loadDashboard(initialDashboard.id);
    } catch (exception) {
        loading.value = false;

        error.value = exception instanceof Error
            ? exception.message
            : 'Your dashboards could not be loaded.';
    }
}

function changeDashboard(event: Event): void {
    const target = event.target;

    if (!(target instanceof HTMLSelectElement)) {
        return;
    }

    const dashboardId = Number(target.value);

    if (Number.isInteger(dashboardId)) {
        void loadDashboard(dashboardId);
    }
}

function dashboardCreated(
    created: EmployeeDashboard,
): void {
    void loadDashboards(created.id);
}

function widgetCreated(): void {
    if (selectedDashboardId.value !== null) {
        void loadDashboard(
            selectedDashboardId.value,
        );
    }
}

function dashboardUpdated(
    updated: EmployeeDashboard,
): void {
    void loadDashboards(updated.id);
}

function dashboardDeleted(
    dashboardId: number,
): void {
    if (selectedDashboardId.value === dashboardId) {
        selectedDashboardId.value = null;
        dashboard.value = null;
        results.value = [];
        resultsMeta.value = null;
    }

    void loadDashboards();
}

function widgetUpdated(): void {
    if (selectedDashboardId.value !== null) {
        void loadDashboard(
            selectedDashboardId.value,
        );
    }
}

function refresh(): void {
    if (selectedDashboardId.value !== null) {
        void loadDashboard(selectedDashboardId.value);
    }
}

onMounted(() => {
    void loadDashboards();
});
</script>

<template>
    <section aria-labelledby="employee-dashboards-heading">
        <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold tracking-wider text-amber-700 uppercase">
                    Personal analytics
                </p>

                <h2
                    id="employee-dashboards-heading"
                    class="mt-2 text-2xl font-bold text-slate-950"
                >
                    My dashboards
                </h2>

                <p class="mt-2 max-w-3xl text-sm text-slate-600">
                    Reusable views built from your governed saved reports.
                </p>
            </div>

            <div
                v-if="dashboards.length > 0"
                class="flex flex-wrap items-end gap-3"
            >
                <label class="text-sm font-semibold text-slate-700">
                    Dashboard

                    <select
                        :value="selectedDashboardId ?? ''"
                        class="mt-1 block min-w-56 rounded-lg border bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-200 focus:outline-none"
                        @change="changeDashboard"
                    >
                        <option
                            v-for="candidate in dashboards"
                            :key="candidate.id"
                            :value="candidate.id"
                        >
                            {{ candidate.name }}
                            {{ candidate.isDefault ? ' (Default)' : '' }}
                        </option>
                    </select>
                </label>

                <button
                    type="button"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="loading"
                    @click="refresh"
                >
                    Refresh
                </button>
            </div>
        </header>

        <EmployeeDashboardCreateForm
            :store-url="bootstrap.storeUrl"
            @created="dashboardCreated"
        />

        <div
            v-if="error !== null"
            role="alert"
            class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"
        >
            {{ error }}
        </div>

        <div
            v-else-if="loading"
            class="rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm"
        >
            <p class="font-semibold text-slate-800">
                Loading governed dashboard results…
            </p>
        </div>

        <div
            v-else-if="dashboards.length === 0"
            class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm"
        >
            <h3 class="text-lg font-semibold text-slate-900">
                No personal dashboards yet
            </h3>

            <p class="mt-2 text-sm text-slate-600">
                Create a dashboard and add widgets from your saved reports.
            </p>
        </div>

        <template v-else-if="dashboard !== null">
            <EmployeeDashboardSettingsForm
                :dashboard="dashboard"
                @updated="dashboardUpdated"
                @deleted="dashboardDeleted"
            />

            <EmployeeDashboardWidgetCreateForm
                :dashboard="dashboard"
                :saved-reports="bootstrap.savedReports"
                @created="widgetCreated"
            />

            <EmployeeDashboardWidgetSettingsForm
                :dashboard="dashboard"
                @updated="widgetUpdated"
            />

            <div
                v-if="resultsMeta !== null"
                class="mb-4 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500"
            >
                <span>
                    Generated:
                    <strong class="text-slate-700">
                        {{ generatedAtLabel }}
                    </strong>
                </span>

                <span>
                    Reporting timezone:
                    <strong class="text-slate-700">
                        {{ resultsMeta.reportingTimezone }}
                    </strong>
                </span>
            </div>

            <EmployeeDashboardGrid
                :dashboard="dashboard"
                :results="results"
                :deleting-widget-id="deletingWidgetId"
                @delete-widget="deleteWidget"
            />
        </template>
    </section>
</template>
