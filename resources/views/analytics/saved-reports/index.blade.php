@extends('layouts.app')

@section('title', 'My Saved Reports')

@section('content')
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        @if (session('status'))
            <div class="border-b border-emerald-200 rounded-t-xl bg-emerald-50 px-6 py-4 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <header
            class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 p-6"
        >
            <div>
                <p class="text-sm font-medium uppercase tracking-wider text-blue-700">
                    Analytics workspace
                </p>

                <h1 class="mt-2 text-3xl font-semibold text-slate-950">
                    {{ $showingTrash ? 'Deleted reports' : 'My saved reports' }}
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    Private semantic report definitions owned by your employee profile.
                </p>
            </div>

            <div class="flex flex-row gap-2">
                <a
                    href="{{ $showingTrash
                ? route('analytics.saved-reports.index')
                : route('analytics.saved-reports.trash') }}"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    {{ $showingTrash ? 'Back to reports' : 'Recycle bin' }}
                </a>
                @if(!$showingTrash)
                    <a
                        href="{{ route('analytics.report-builder') }}"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                    >
                        Create report
                    </a>
                @endif
            </div>
        </header>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                <tr class="text-left text-slate-600">
                    <th class="px-6 py-3 font-semibold">Report</th>
                    <th class="px-6 py-3 font-semibold">Dataset</th>
                    <th class="px-6 py-3 font-semibold">Definition</th>
                    <th class="px-6 py-3 font-semibold">Version</th>
                    <th class="px-6 py-3 font-semibold">Updated</th>
                    <th class="px-6 py-3 text-right font-semibold">Actions</th>
                </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                @forelse ($reports as $report)
                    <tr>
                        <td class="px-6 py-4">
                            @if ($showingTrash)
                                <span class="font-medium text-slate-700">
                                    {{ $report->name }}
                                </span>
                            @else
                                <a
                                    href="{{ route('analytics.report-builder', ['savedReport' => $report]) }}"
                                    class="font-medium text-blue-700 hover:underline"
                                >
                                    {{ $report->name }}
                                </a>
                            @endif

                            @if ($report->description !== null)
                                <p class="mt-1 max-w-md text-slate-500">
                                    {{ $report->description }}
                                </p>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-slate-700">
                            {{ $datasetLabels[$report->dataset->value] ?? $report->dataset->value }}
                        </td>

                        <td class="px-6 py-4 text-slate-700">
                            {{ count($report->definition['dimensions'] ?? []) }}
                            dimensions ·
                            {{ count($report->definition['measures'] ?? []) }}
                            measures ·
                            {{ count($report->definition['filters'] ?? []) }}
                            filters
                        </td>

                        <td class="px-6 py-4 text-slate-700">
                            v{{ $report->definition_version }}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-slate-700">
                            {{ $report->updated_at?->format('Y-m-d H:i') }}
                        </td>

                        <td class="px-6 py-4 text-right">
                            @if ($showingTrash)
                                <form
                                    method="POST"
                                    action="{{ route('analytics.saved-reports.restore', $report->getKey()) }}"
                                    onsubmit="return confirm('Restore this report?')"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="text-sm font-medium text-blue-700 hover:underline"
                                    >
                                        Restore
                                    </button>
                                </form>
                            @else
                                <form
                                    method="POST"
                                    action="{{ route('analytics.saved-reports.destroy', $report) }}"
                                    onsubmit="return confirm('Move this report to the recycle bin?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="text-sm font-medium text-red-700 hover:underline"
                                    >
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="5"
                            class="px-6 py-12 text-center text-slate-500"
                        >
                            You have no saved reports yet.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($reports->hasPages())
            <footer class="border-t border-slate-200 px-6 py-4">
                {{ $reports->links() }}
            </footer>
        @endif
    </section>
@endsection
