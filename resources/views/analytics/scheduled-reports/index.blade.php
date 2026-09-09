@extends('layouts.app')

@section('title', 'My Schedules')

@section('content')
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 p-6">
            <div>
                <p class="text-sm font-medium uppercase tracking-wider text-blue-700">
                    Automated reporting
                </p>

                <h1 class="mt-2 text-3xl font-semibold text-slate-950">
                    My schedules
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    Recurring exports are dispatched according to each schedule’s
                    local timezone.
                </p>
            </div>

            <a
                href="{{ route('analytics.saved-reports.index') }}"
                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
            >
                Choose a report
            </a>
        </header>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                <tr class="text-left text-slate-600">
                    <th class="px-6 py-3 font-semibold">Schedule</th>
                    <th class="px-6 py-3 font-semibold">Report</th>
                    <th class="px-6 py-3 font-semibold">Recurrence</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 font-semibold">Next run</th>
                    <th class="px-6 py-3 font-semibold">Last dispatched</th>
                    <th class="px-6 py-3 text-right font-semibold">
                        Actions
                    </th>
                </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                @forelse ($schedules as $schedule)
                    <tr>
                        <td class="px-6 py-4">
                            <p class="font-medium text-slate-900">
                                {{ $schedule->name }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ strtoupper($schedule->format->value) }}
                            </p>
                        </td>

                        <td class="px-6 py-4 text-slate-700">
                            {{ $schedule->savedReport?->name ?? 'Deleted report' }}
                        </td>

                        <td class="px-6 py-4 text-slate-700">
                            <p>{{ ucfirst($schedule->frequency->value) }}</p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ substr((string) $schedule->run_time, 0, 5) }}
                                · {{ $schedule->timezone }}

                                @if ($schedule->frequency->value === 'weekly')
                                    · weekday {{ $schedule->weekday }}
                                @elseif ($schedule->frequency->value === 'monthly')
                                    · day {{ $schedule->day_of_month }}
                                @endif
                            </p>
                        </td>

                        <td class="px-6 py-4">
                                <span
                                    @class([
                                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-emerald-100 text-emerald-800' =>
                                            $schedule->status->value === 'active',
                                        'bg-slate-200 text-slate-700' =>
                                            $schedule->status->value === 'paused',
                                    ])
                                >
                                    {{ ucfirst($schedule->status->value) }}
                                </span>
                        </td>

                        <td class="whitespace-nowrap px-6 py-4 text-slate-700">
                            {{ $schedule->next_run_at
                                ->setTimezone($schedule->timezone)
                                ->format('Y-m-d H:i T') }}
                        </td>

                        <td class="whitespace-nowrap px-6 py-4 text-slate-700">
                            {{ $schedule->last_dispatched_at
                                ?->setTimezone($schedule->timezone)
                                ->format('Y-m-d H:i T') ?? 'Never' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            @can('update', $schedule)
                                @if ($schedule->status->value === 'active')
                                    <form
                                        method="POST"
                                        action="{{ route('analytics.scheduled-reports.pause', $schedule) }}"
                                        onsubmit="return confirm('Pause this scheduled report?')"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 transition hover:bg-amber-100"
                                        >
                                            Pause
                                        </button>
                                    </form>
                                @elseif ($schedule->savedReport !== null)
                                    @can('schedule', $schedule->savedReport)
                                        <form
                                            method="POST"
                                            action="{{ route('analytics.scheduled-reports.resume', $schedule) }}"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="rounded-lg border border-emerald-600 bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700"
                                            >
                                                Resume
                                            </button>
                                        </form>
                                    @endcan
                                @else
                                    <span class="text-xs text-slate-400">
                                        Report unavailable
                                    </span>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                            You have no scheduled reports yet.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($schedules->hasPages())
            <footer class="border-t border-slate-200 px-6 py-4">
                {{ $schedules->links() }}
            </footer>
        @endif
    </section>
@endsection
