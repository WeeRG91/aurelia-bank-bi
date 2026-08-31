@extends('layouts.app')

@section('title', 'Branches')

@section('content')
    <div class="rounded-xl bg-white p-8 shadow-sm">
        <div>
            <p class="text-sm font-medium uppercase tracking-wider text-amber-700">
                Operational network
            </p>

            <h1 class="mt-2 text-3xl font-semibold">Branches</h1>
        </div>

        <div class="mt-8 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead>
                <tr class="text-left text-sm text-slate-500">
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Location</th>
                    <th class="px-4 py-3">Opened</th>
                </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                @forelse ($branches as $branch)
                    <tr>
                        <td class="px-4 py-4 font-medium">
                            <a
                                href="{{ route('branches.show', $branch) }}"
                                class="text-amber-700 hover:underline"
                            >
                                {{ $branch->branch_code }}
                            </a>
                        </td>
                        <td class="px-4 py-4">{{ $branch->name }}</td>
                        <td class="px-4 py-4">
                            {{ $branch->city }}, {{ $branch->country_code }}
                        </td>
                        <td class="px-4 py-4">
                            {{ $branch->opened_at->format('Y-m-d') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-slate-500">
                            No accessible branches found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $branches->links() }}
        </div>
    </div>
@endsection
