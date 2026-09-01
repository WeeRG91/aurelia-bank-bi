@extends('layouts.app')

@section('title', $employee->user->name)

@section('content')
    <div class="rounded-xl bg-white p-8 shadow-sm">
        @can('viewAny', \App\Models\Employee::class)
            <a
                href="{{ route('employees.index') }}"
                class="text-sm font-medium text-amber-700 hover:underline"
            >
                ← Back to employees
            </a>
        @endcan

        <h1 class="mt-4 text-3xl font-semibold">
            {{ $employee->user->name }}
        </h1>

        <p class="mt-1 text-slate-500">
            {{ $employee->job_title }}
        </p>

        <dl class="mt-8 grid gap-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-slate-500">Employee number</dt>
                <dd class="mt-1 font-medium">{{ $employee->employee_number }}</dd>
            </div>

            <div>
                <dt class="text-sm text-slate-500">Email</dt>
                <dd class="mt-1 font-medium">{{ $employee->user->email }}</dd>
            </div>

            <div>
                <dt class="text-sm text-slate-500">Role</dt>
                <dd class="mt-1 font-medium">
                    {{ str($employee->role->value)->replace('_', ' ')->title() }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-slate-500">Department</dt>
                <dd class="mt-1 font-medium">
                    {{ str($employee->department->value)->replace('_', ' ')->title() }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-slate-500">Branch</dt>
                <dd class="mt-1 font-medium">
                    {{ $employee->branch?->name ?? 'Head office' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-slate-500">Status</dt>
                <dd class="mt-1 font-medium">
                    {{ str($employee->status->value)->title() }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-slate-500">Hired</dt>
                <dd class="mt-1 font-medium">
                    {{ $employee->hired_at->format('F j, Y') }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-slate-500">Termination date</dt>
                <dd class="mt-1 font-medium">
                    {{ $employee->terminated_at?->format('F j, Y') ?? 'Not applicable' }}
                </dd>
            </div>
        </dl>
    </div>
@endsection
