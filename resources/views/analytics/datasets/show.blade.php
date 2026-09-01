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
    </div>
@endsection
