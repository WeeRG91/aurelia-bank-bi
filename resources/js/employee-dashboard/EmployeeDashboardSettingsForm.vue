<script setup lang="ts">
import { ref, watch } from 'vue';

import type {
    EmployeeDashboard,
    EmployeeDashboardResponse,
} from './types';

const props = defineProps<{
    dashboard: EmployeeDashboard;
}>();

const emit = defineEmits<{
    updated: [dashboard: EmployeeDashboard];
    deleted: [dashboardId: number];
}>();

const name = ref('');
const description = ref('');
const isDefault = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);
const success = ref<string | null>(null);
const deleting = ref(false);

function populateForm(): void {
    name.value = props.dashboard.name;
    description.value = props.dashboard.description ?? '';
    isDefault.value = props.dashboard.isDefault;
    error.value = null;
    success.value = null;
}

watch(
    () => props.dashboard,
    populateForm,
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
    saving.value = true;
    error.value = null;
    success.value = null;

    try {
        const response = await fetch(
            props.dashboard.updateUrl,
            {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    name: name.value,
                    description:
                        description.value.trim() === ''
                            ? null
                            : description.value,
                    is_default: isDefault.value,
                }),
            },
        );

        const payload = await response.json() as
            | EmployeeDashboardResponse
            | { message?: string };

        if (!response.ok) {
            throw new Error(
                'message' in payload
                && typeof payload.message === 'string'
                    ? payload.message
                    : 'The dashboard could not be updated.',
            );
        }

        const updated = (
            payload as EmployeeDashboardResponse
        ).data;

        success.value = 'Dashboard settings updated.';

        emit('updated', updated);
    } catch (exception) {
        error.value = exception instanceof Error
            ? exception.message
            : 'The dashboard could not be updated.';
    } finally {
        saving.value = false;
    }
}

async function deleteDashboard(): Promise<void> {
    const confirmed = window.confirm(
        `Delete the dashboard “${props.dashboard.name}” and its widgets?`,
    );

    if (!confirmed) {
        return;
    }

    deleting.value = true;
    error.value = null;
    success.value = null;

    try {
        const response = await fetch(
            props.dashboard.destroyUrl,
            {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            },
        );

        if (!response.ok) {
            const payload = await response.json()
                .catch(() => null) as {
                message?: string;
            } | null;

            throw new Error(
                payload?.message
                ?? `Dashboard deletion failed with status ${response.status}.`,
            );
        }

        emit('deleted', props.dashboard.id);
    } catch (exception) {
        error.value = exception instanceof Error
            ? exception.message
            : 'The dashboard could not be deleted.';
    } finally {
        deleting.value = false;
    }
}
</script>

<template>
    <details class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <summary class="cursor-pointer px-5 py-4 font-semibold text-slate-900">
            Dashboard settings
        </summary>

        <form
            class="border-t border-slate-200 p-5"
            @submit.prevent="submit"
        >
            <div class="grid gap-4 md:grid-cols-2">
                <label class="text-sm font-semibold text-slate-700">
                    Name

                    <input
                        v-model="name"
                        required
                        maxlength="150"
                        class="mt-1 block w-full rounded-lg border px-3 py-2 font-normal shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-200 focus:outline-none"
                    >
                </label>

                <label class="text-sm font-semibold text-slate-700">
                    Description

                    <input
                        v-model="description"
                        maxlength="2000"
                        class="mt-1 block w-full rounded-lg border px-3 py-2 font-normal shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-200 focus:outline-none"
                    >
                </label>
            </div>

            <label class="mt-4 flex items-center gap-2 text-sm text-slate-700">
                <input
                    v-model="isDefault"
                    type="checkbox"
                    class="rounded border-slate-300 text-amber-700 focus:ring-amber-500"
                >

                Make this my default dashboard
            </label>

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

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                <button
                    type="submit"
                    class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="saving || deleting"
                >
                    {{ saving ? 'Saving…' : 'Save settings' }}
                </button>

                <button
                    type="button"
                    class="rounded-lg border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 shadow-sm hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="saving || deleting"
                    @click="deleteDashboard"
                >
                    {{ deleting ? 'Deleting…' : 'Delete dashboard' }}
                </button>
            </div>
        </form>
    </details>
</template>
