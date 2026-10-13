<script setup lang="ts">
import { ref } from 'vue';

import type {
    EmployeeDashboard,
    EmployeeDashboardResponse,
} from './types';

const props = defineProps<{
    storeUrl: string;
}>();

const emit = defineEmits<{
    created: [dashboard: EmployeeDashboard];
}>();

const name = ref('');
const description = ref('');
const isDefault = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    )?.content ?? '';
}

async function submit(): Promise<void> {
    saving.value = true;
    error.value = null;

    try {
        const response = await fetch(props.storeUrl, {
            method: 'POST',
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
        });

        const payload = await response.json() as
            | EmployeeDashboardResponse
            | { message?: string };

        if (!response.ok) {
            throw new Error(
                'message' in payload
                && typeof payload.message === 'string'
                    ? payload.message
                    : 'The dashboard could not be created.',
            );
        }

        const created = (
            payload as EmployeeDashboardResponse
        ).data;

        name.value = '';
        description.value = '';
        isDefault.value = false;

        emit('created', created);
    } catch (exception) {
        error.value = exception instanceof Error
            ? exception.message
            : 'The dashboard could not be created.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <details class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <summary class="cursor-pointer px-5 py-4 font-semibold text-slate-900">
            Create a dashboard
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
                        maxlength="1000"
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

            <button
                type="submit"
                :disabled="saving"
                class="mt-5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ saving ? 'Creating…' : 'Create dashboard' }}
            </button>
        </form>
    </details>
</template>
