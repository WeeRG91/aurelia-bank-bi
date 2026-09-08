@extends('layouts.app')

@section('title', 'My Exports')

@section('content')
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <header
            class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 p-6"
        >
            <div>
                <p class="text-sm font-medium uppercase tracking-wider text-blue-700">
                    Analytics workspace
                </p>

                <h1 class="mt-2 text-3xl font-semibold text-slate-950">
                    My exports
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    Background report exports are private and expire automatically.
                </p>
            </div>

            <a
                href="{{ route('analytics.report-exports.index') }}"
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                Refresh
            </a>
        </header>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                <tr class="text-left text-slate-600">
                    <th class="px-6 py-3 font-semibold">Report</th>
                    <th class="px-6 py-3 font-semibold">Format</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 font-semibold">Rows</th>
                    <th class="px-6 py-3 font-semibold">Requested</th>
                    <th class="px-6 py-3 text-right font-semibold">Action</th>
                </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                @forelse ($exports as $export)
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">
                            {{ $export->savedReport?->name ?? 'Deleted report' }}
                        </td>

                        <td class="px-6 py-4 text-slate-700">
                            {{ strtoupper($export->format->value) }}
                        </td>

                        <td class="px-6 py-4">
                                <span
                                    @class([
                                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-amber-100 text-amber-800' => $export->status->value === 'queued',
                                        'bg-blue-100 text-blue-800' => $export->status->value === 'processing',
                                        'bg-emerald-100 text-emerald-800' => $export->status->value === 'completed',
                                        'bg-red-100 text-red-800' => $export->status->value === 'failed',
                                        'bg-slate-200 text-slate-700' => $export->status->value === 'expired',
                                    ])
                                >
                                    {{ ucfirst($export->status->value) }}
                                </span>
                        </td>

                        <td class="px-6 py-4 text-slate-700">
                            {{ $export->row_count === null
                                ? '—'
                                : number_format($export->row_count) }}
                        </td>

                        <td class="whitespace-nowrap px-6 py-4 text-slate-700">
                            {{ $export->created_at?->format('Y-m-d H:i') }}
                        </td>

                        <td class="px-6 py-4 text-right">
                            @can('download', $export)
                                <a
                                    href="{{ route('analytics.report-exports.download', $export) }}"
                                    class="font-medium text-blue-700 hover:underline"
                                >
                                    Download
                                </a>
                            @elseif ($export->status->value === 'failed')
                                <span
                                    class="text-xs text-red-700"
                                    title="{{ $export->failure_message }}"
                                >
                                        Failed
                                    </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="6"
                            class="px-6 py-12 text-center text-slate-500"
                        >
                            You have not requested any background exports.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($exports->hasPages())
            <footer class="border-t border-slate-200 px-6 py-4">
                {{ $exports->links() }}
            </footer>
        @endif
    </section>
@endsection
