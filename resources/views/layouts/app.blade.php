<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Aurelia Bank BI')</title>

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <a href="{{ route('dashboard') }}" class="text-lg font-semibold">
            Aurelia Bank BI
        </a>

        @auth
            <nav class="flex items-center gap-4">
                <a
                    href="{{ route('branches.index') }}"
                    class="text-sm font-medium text-slate-700 hover:text-amber-700"
                >
                    Branches
                </a>

                <a
                    href="{{ route('profile') }}"
                    class="text-sm font-medium text-slate-700 hover:text-amber-700"
                >
                    My profile
                </a>

                @can('viewAny', \App\Models\Employee::class)
                    <a
                        href="{{ route('employees.index') }}"
                        class="text-sm font-medium text-slate-700 hover:text-amber-700"
                    >
                        Employees
                    </a>
                @endcan

                <a
                    href="{{ route('analytics.datasets.index') }}"
                    class="text-sm font-medium text-slate-700 hover:text-amber-700"
                >
                    Datasets
                </a>

                <a
                    href="{{ route('analytics.report-builder') }}"
                    class="text-sm font-medium text-slate-700 hover:text-amber-700"
                >
                    Report Builder
                </a>
            </nav>

            <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-600">
                        {{ auth()->user()->name }}
                    </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button
                        type="submit"
                        class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white"
                    >
                        Sign out
                    </button>
                </form>
            </div>
        @endauth
    </div>
</header>

<main class="mx-auto max-w-6xl px-6 py-10">
    @yield('content')
</main>
</body>
</html>
