@extends('layouts.app')

@section('title', 'Report Builder — Aurelia Bank BI')

@section('content')
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-wider text-amber-700">
            Analytics workspace
        </p>

        <h1 class="mt-2 text-3xl font-bold text-slate-950">
            Report Builder
        </h1>

        <p class="mt-3 max-w-3xl text-slate-600">
            Select an authorized dataset to begin defining an analytical report.
        </p>
    </div>

    <div id="report-builder-app" data-report-builder></div>

    <script
        id="report-builder-bootstrap"
        type="application/json"
    >{!! $bootstrapJson !!}</script>
@endsection
