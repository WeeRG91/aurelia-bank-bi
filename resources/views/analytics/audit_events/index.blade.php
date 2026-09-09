@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-amber-700">
                Governance
            </p>

            <h1 class="mt-1 text-2xl font-bold text-slate-900">
                Analytics Audit Trail
            </h1>

            <p class="mt-2 text-sm text-slate-600">
                Immutable analytics activity shown in
                {{ $reportingTimezone }} time.
            </p>
        </div>

        @php
            $activeFilterCount = collect([
                request('action'),
                request('outcome'),
                request('source'),
                request('dataset'),
                request('actor_employee_id'),
                request('from'),
                request('to'),
            ])->filter(
                static fn ($value) => $value !== null && $value !== '',
            )->count();
        @endphp

        <form
            method="GET"
            action="{{ route('analytics.audit-events.index') }}"
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        >
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/80 px-5 py-4">
                <div class="flex items-center gap-3">
            <span class="flex size-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    class="size-5"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 5h18M6 12h12M10 19h4"
                    />
                </svg>
            </span>

                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">
                            Filter audit activity
                        </h2>

                        <p class="text-xs text-slate-500">
                            Narrow events by activity, origin, employee, or date.
                        </p>
                    </div>
                </div>

                @if ($activeFilterCount > 0)
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                {{ $activeFilterCount }}
                active {{ Str::plural('filter', $activeFilterCount) }}
            </span>
                @endif
            </div>

            <div class="space-y-5 p-5">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <label class="block">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Action
                </span>

                        <select
                            name="action"
                            class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition focus:border-amber-500 focus:bg-white focus:ring-4 focus:ring-amber-100"
                        >
                            <option value="">All actions</option>

                            @foreach ($actions as $action)
                                <option
                                    value="{{ $action->value }}"
                                    @selected(request('action') === $action->value)
                                >
                                    {{ str($action->value)->replace('_', ' ')->title() }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Outcome
                </span>

                        <select
                            name="outcome"
                            class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition focus:border-amber-500 focus:bg-white focus:ring-4 focus:ring-amber-100"
                        >
                            <option value="">All outcomes</option>

                            @foreach ($outcomes as $outcome)
                                <option
                                    value="{{ $outcome->value }}"
                                    @selected(request('outcome') === $outcome->value)
                                >
                                    {{ str($outcome->value)->title() }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Source
                </span>

                        <select
                            name="source"
                            class="mt-1.5 w-full rounded-xl border bg-slate-50 px-3 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-100"
                        >
                            <option value="">All sources</option>

                            @foreach ($sources as $source)
                                <option
                                    value="{{ $source->value }}"
                                    @selected(request('source') === $source->value)
                                >
                                    {{ str($source->value)->title() }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Dataset
                </span>

                        <select
                            name="dataset"
                            class="mt-1.5 w-full rounded-xl border bg-slate-50 px-3 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-100"
                        >
                            <option value="">All datasets</option>

                            @foreach ($datasets as $dataset)
                                <option
                                    value="{{ $dataset->value }}"
                                    @selected(request('dataset') === $dataset->value)
                                >
                                    {{ str($dataset->value)->replace('_', ' ')->title() }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-3">
                    <label class="block">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Employee ID
                </span>

                        <input
                            name="actor_employee_id"
                            type="number"
                            min="1"
                            placeholder="Any employee"
                            value="{{ request('actor_employee_id') }}"
                            class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-amber-500 focus:bg-white focus:ring-4 focus:ring-amber-100"
                        >
                    </label>

                    <label class="block">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    From date
                </span>

                        <input
                            name="from"
                            type="date"
                            value="{{ request('from') }}"
                            class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition focus:border-amber-500 focus:bg-white focus:ring-4 focus:ring-amber-100"
                        >
                    </label>

                    <label class="block">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    To date
                </span>

                        <input
                            name="to"
                            type="date"
                            value="{{ request('to') }}"
                            class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition focus:border-amber-500 focus:bg-white focus:ring-4 focus:ring-amber-100"
                        >
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5">
                    @if ($activeFilterCount > 0)
                        <a
                            href="{{ route('analytics.audit-events.index') }}"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                        >
                            Clear filters
                        </a>
                    @endif

                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-200"
                    >
                        Apply filters

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="size-4"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m9 18 6-6-6-6"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Time</th>
                        <th class="px-4 py-3">Actor</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Resource</th>
                        <th class="px-4 py-3">Outcome</th>
                        <th class="px-4 py-3">Details</th>
                    </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                    @forelse ($events as $event)
                        <tr class="align-top">
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">
                                {{ $event->occurred_at->setTimezone($reportingTimezone)->format('Y-m-d H:i:s T') }}
                            </td>

                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900">
                                    {{ $event->actor?->user?->name ?? 'System' }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $event->actor?->employee_number ?? 'No employee actor' }}
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900">
                                    {{ str($event->action->value)->replace('_', ' ')->title() }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $event->source->value }}
                                </div>
                            </td>

                            <td class="px-4 py-4 text-slate-700">
                                <div>{{ $event->subject_type ?? '—' }}</div>
                                <div class="font-mono text-xs text-slate-500">
                                    {{ $event->subject_id ?? '—' }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $event->dataset?->value ?? 'No dataset' }}
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                    <span
                                        @class([
                                            'rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-emerald-100 text-emerald-800' => $event->outcome->value === 'succeeded',
                                            'bg-amber-100 text-amber-800' => $event->outcome->value === 'denied',
                                            'bg-red-100 text-red-800' => $event->outcome->value === 'failed',
                                        ])
                                    >
                                        {{ str($event->outcome->value)->title() }}
                                    </span>
                            </td>

                            <td class="px-4 py-4">
                                <details>
                                    <summary class="cursor-pointer font-medium text-blue-700">
                                        Inspect
                                    </summary>

                                    <dl class="mt-2 space-y-1 text-xs text-slate-600">
                                        <div>
                                            <dt class="inline font-semibold">Request:</dt>
                                            <dd class="inline font-mono">
                                                {{ $event->request_id ?? '—' }}
                                            </dd>
                                        </div>

                                        <div>
                                            <dt class="inline font-semibold">IP:</dt>
                                            <dd class="inline">
                                                {{ $event->ip_address ?? '—' }}
                                            </dd>
                                        </div>

                                        @foreach ($event->context as $key => $value)
                                            <div>
                                                <dt class="inline font-semibold">
                                                    {{ str($key)->replace('_', ' ')->title() }}:
                                                </dt>
                                                <dd class="inline">
                                                    {{ is_bool($value) ? ($value ? 'Yes' : 'No') : ($value ?? '—') }}
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                No audit events match these filters.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-4">
                {{ $events->links() }}
            </div>
        </div>
    </div>
@endsection
