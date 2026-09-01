@extends('layouts.app')

@section('title', $dataset->label)

@section('content')
    <div class="rounded-xl bg-white p-8 shadow-sm">
        <a
            href="{{ route('analytics.datasets.index') }}"
            class="text-sm font-medium text-amber-700 hover:underline"
        >
            ← Back to datasets
        </a>

        <div class="mt-6 flex items-start justify-between gap-6">
            <div>
                <p class="font-mono text-xs text-slate-400">
                    {{ $dataset->key->value }}
                </p>

                <h1 class="mt-2 text-3xl font-semibold">
                    {{ $dataset->label }}
                </h1>
            </div>

            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium uppercase text-slate-600">
                {{ $dataset->status->value }}
            </span>
        </div>

        <p class="mt-6 max-w-3xl leading-7 text-slate-600">
            {{ $dataset->description }}
        </p>

        @if ($dataset->status === \App\Analytics\Datasets\DatasetStatus::DRAFT)
            <div class="mt-8 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                This dataset is registered but cannot execute analytics
                queries yet.
            </div>
        @endif

        <div class="mt-8 rounded-lg bg-slate-50 p-4">
            <dt class="text-sm font-medium text-slate-500">
                Dataset grain
            </dt>

            <dd class="mt-1 text-sm text-slate-900">
                {{ $dataset->grain }}
            </dd>
        </div>

        <section class="mt-10 border-t border-slate-200 pt-8">
            <h2 class="text-xl font-semibold">Dimensions</h2>

            <p class="mt-2 text-sm text-slate-600">
                Controlled business fields available for future grouping
                and filtering.
            </p>

            @if ($dataset->dimensions() === [])
                <div class="mt-6 rounded-lg bg-slate-50 p-4 text-sm text-slate-500">
                    No semantic dimensions have been defined for this dataset yet.
                </div>
            @else
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead>
                        <tr class="text-left text-sm text-slate-500">
                            <th class="px-4 py-3">Dimension</th>
                            <th class="px-4 py-3">Kind</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Sensitivity</th>
                            <th class="px-4 py-3">Nullable</th>
                        </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                        @foreach ($dataset->dimensions() as $dimension)
                            <tr>
                                <td class="px-4 py-4">
                                    <div class="font-medium">
                                        {{ $dimension->label }}
                                    </div>

                                    <div class="mt-1 font-mono text-xs text-slate-400">
                                        {{ $dimension->key }}
                                    </div>

                                    <div class="mt-2 max-w-md text-sm text-slate-500">
                                        {{ $dimension->description }}
                                    </div>

                                    <div class="mt-6 rounded-lg bg-slate-50 p-4">
                                        <dt class="text-sm font-medium text-slate-500">
                                            Dataset grain
                                        </dt>

                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ $dataset->grain }}
                                        </dd>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    {{ str($dimension->kind->value)->title() }}
                                </td>

                                <td class="px-4 py-4">
                                    {{ str($dimension->dataType->value)->title() }}
                                </td>

                                <td class="px-4 py-4">
                                    {{ str($dimension->sensitivity->value)->title() }}
                                </td>

                                <td class="px-4 py-4">
                                    {{ $dimension->nullable ? 'Yes' : 'No' }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="mt-10 border-t border-slate-200 pt-8">
            <h2 class="text-xl font-semibold">Measures</h2>

            <p class="mt-2 text-sm text-slate-600">
                Governed numeric values available for aggregation and reporting.
            </p>

            @if ($dataset->measures() === [])
                <div class="mt-6 rounded-lg bg-slate-50 p-4 text-sm text-slate-500">
                    No semantic measures have been defined for this dataset yet.
                </div>
            @else
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead>
                        <tr class="text-left text-sm text-slate-500">
                            <th class="px-4 py-3">Measure</th>
                            <th class="px-4 py-3">Aggregation</th>
                            <th class="px-4 py-3">Result type</th>
                            <th class="px-4 py-3">Sensitivity</th>
                            <th class="px-4 py-3">Currency context</th>
                        </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                        @foreach ($dataset->measures() as $measure)
                            <tr>
                                <td class="px-4 py-4">
                                    <div class="font-medium">
                                        {{ $measure->label }}
                                    </div>

                                    <div class="mt-1 font-mono text-xs text-slate-400">
                                        {{ $measure->key }}
                                    </div>

                                    <div class="mt-2 max-w-md text-sm text-slate-500">
                                        {{ $measure->description }}
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    {{ str($measure->aggregation->value)->title() }}
                                </td>

                                <td class="px-4 py-4">
                                    {{ str($measure->dataType->value)->title() }}
                                </td>

                                <td class="px-4 py-4">
                                    {{ str($measure->sensitivity->value)->title() }}
                                </td>

                                <td class="px-4 py-4">
                                    @if ($measure->currencyDimension !== null)
                                        <div>
                                            {{ $dataset->dimension($measure->currencyDimension)->label }}
                                        </div>

                                        <div class="mt-1 font-mono text-xs text-slate-400">
                                            {{ $measure->currencyDimension }}
                                        </div>
                                    @else
                                        <span class="text-slate-400">
                                    Not applicable
                                </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
