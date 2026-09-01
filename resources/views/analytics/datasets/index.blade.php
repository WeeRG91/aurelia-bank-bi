@extends('layouts.app')

@section('title', 'Analytics Datasets')

@section('content')
    <div>
        <p class="text-sm font-medium uppercase tracking-wider text-amber-700">
            Analytics catalog
        </p>

        <h1 class="mt-2 text-3xl font-semibold">Datasets</h1>

        <p class="mt-3 max-w-3xl text-slate-600">
            Controlled business datasets available to your employee role.
            Dataset definitions never expose arbitrary tables or SQL.
        </p>

        <div class="mt-8 grid gap-6 md:grid-cols-2">
            @forelse ($datasets as $dataset)
                <a
                    href="{{ route('analytics.datasets.show', $dataset->key->value) }}"
                    class="rounded-xl bg-white p-6 shadow-sm transition hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-4">
                        <h2 class="text-xl font-semibold">
                            {{ $dataset->label }}
                        </h2>

                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium uppercase text-slate-600">
                            {{ $dataset->status->value }}
                        </span>
                    </div>

                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        {{ $dataset->description }}
                    </p>

                    <p class="mt-5 font-mono text-xs text-slate-400">
                        {{ $dataset->key->value }}
                    </p>
                </a>
            @empty
                <div class="rounded-xl bg-white p-8 text-slate-600 shadow-sm md:col-span-2">
                    No active analytics datasets are currently available
                    for your role.
                </div>
            @endforelse
        </div>
    </div>
@endsection
