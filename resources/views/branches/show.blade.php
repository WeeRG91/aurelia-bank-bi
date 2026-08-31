@extends('layouts.app')

@section('title', $branch->name)

@section('content')
    <div class="rounded-xl bg-white p-8 shadow-sm">
        <a
            href="{{ route('branches.index') }}"
            class="text-sm font-medium text-amber-700 hover:underline"
        >
            ← Back to branches
        </a>

        <h1 class="mt-4 text-3xl font-semibold">{{ $branch->name }}</h1>

        <dl class="mt-8 grid gap-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-slate-500">Branch code</dt>
                <dd class="mt-1 font-medium">{{ $branch->branch_code }}</dd>
            </div>

            <div>
                <dt class="text-sm text-slate-500">Location</dt>
                <dd class="mt-1 font-medium">
                    {{ $branch->city }}, {{ $branch->country_code }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-slate-500">Opened</dt>
                <dd class="mt-1 font-medium">
                    {{ $branch->opened_at->format('F j, Y') }}
                </dd>
            </div>
        </dl>
    </div>
@endsection
