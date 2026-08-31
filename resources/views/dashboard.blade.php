@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="rounded-xl bg-white p-8 shadow-sm">
        <p class="text-sm font-medium uppercase tracking-wider text-amber-700">
            Authorized employee
        </p>

        <h1 class="mt-2 text-3xl font-semibold">
            Welcome, {{ $user->name }}
        </h1>

        <dl class="mt-8 grid gap-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-slate-500">Employee number</dt>
                <dd class="mt-1 font-medium">
                    {{ $user->employee->employee_number }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-slate-500">Role</dt>
                <dd class="mt-1 font-medium">
                    {{ str($user->employee->role->value)->replace('_', ' ')->title() }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-slate-500">Department</dt>
                <dd class="mt-1 font-medium">
                    {{ str($user->employee->department->value)->replace('_', ' ')->title() }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-slate-500">Branch</dt>
                <dd class="mt-1 font-medium">
                    {{ $user->employee->branch?->name ?? 'Head office' }}
                </dd>
            </div>
        </dl>
    </div>
@endsection
