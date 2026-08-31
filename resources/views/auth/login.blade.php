@extends('layouts.app')

@section('title', 'Employee sign in')

@section('content')
    <div class="mx-auto max-w-md rounded-xl bg-white p-8 shadow-sm">
        <div class="mb-8">
            <p class="text-sm font-medium uppercase tracking-wider text-amber-700">
                Internal access
            </p>

            <h1 class="mt-2 text-2xl font-semibold">
                Employee sign in
            </h1>

            <p class="mt-2 text-sm text-slate-600">
                Use your authorized Aurelia employee account.
            </p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium">
                    Email address
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2"
                >

                @error('email')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">
                    Password
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2"
                >

                @error('password')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full rounded-md bg-amber-700 px-4 py-2.5 font-medium text-white"
            >
                Sign in
            </button>
        </form>
    </div>
@endsection
