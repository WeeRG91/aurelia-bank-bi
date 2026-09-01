@extends('layouts.app')

@section('title', 'Employees')

@section('content')
    <div class="rounded-xl bg-white p-8 shadow-sm">
        <p class="text-sm font-medium uppercase tracking-wider text-amber-700">
            Organization
        </p>

        <h1 class="mt-2 text-3xl font-semibold">Employees</h1>

        <div class="mt-8 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead>
                <tr class="text-left text-sm text-slate-500">
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Branch</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                @forelse ($employees as $employee)
                    <tr>
                        <td class="px-4 py-4">
                            <a
                                href="{{ route('employees.show', $employee) }}"
                                class="font-medium text-amber-700 hover:underline"
                            >
                                {{ $employee->user->name }}
                            </a>

                            <div class="text-sm text-slate-500">
                                {{ $employee->employee_number }}
                            </div>
                        </td>

                        <td class="px-4 py-4">
                            {{ str($employee->role->value)->replace('_', ' ')->title() }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $employee->branch?->name ?? 'Head office' }}
                        </td>

                        <td class="px-4 py-4">
                            {{ str($employee->status->value)->title() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-slate-500">
                            No accessible employees found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $employees->links() }}
        </div>
    </div>
@endsection
