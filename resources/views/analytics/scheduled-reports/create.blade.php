@extends('layouts.app')

@section('title', 'Schedule Report')

@section('content')
    <section class="mx-auto max-w-3xl rounded-xl border border-slate-200 bg-white shadow-sm">
        <header class="border-b border-slate-200 p-6">
            <p class="text-sm font-medium uppercase tracking-wider text-blue-700">
                Scheduled reporting
            </p>

            <h1 class="mt-2 text-3xl font-semibold text-slate-950">
                Schedule {{ $savedReport->name }}
            </h1>

            <p class="mt-2 text-sm text-slate-600">
                The report will be exported automatically using its latest saved
                definition and your data access at dispatch time.
            </p>
        </header>

        <form
            method="POST"
            action="{{ route('analytics.saved-reports.schedules.store', $savedReport) }}"
            class="space-y-6 p-6"
        >
            @csrf

            @if ($errors->any())
                <div
                    role="alert"
                    class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800"
                >
                    <p class="font-semibold">The schedule could not be created.</p>

                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700">
                    Schedule name
                </label>

                <input
                    id="name"
                    name="name"
                    type="text"
                    maxlength="150"
                    required
                    value="{{ old('name', $savedReport->name . ' schedule') }}"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                >
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="format" class="block text-sm font-semibold text-slate-700">
                        Export format
                    </label>

                    <select
                        id="format"
                        name="format"
                        required
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                    >
                        @foreach ($formats as $format)
                            <option
                                value="{{ $format->value }}"
                                @selected(old('format', 'csv') === $format->value)
                            >
                                {{ strtoupper($format->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="frequency" class="block text-sm font-semibold text-slate-700">
                        Frequency
                    </label>

                    <select
                        id="frequency"
                        name="frequency"
                        required
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                    >
                        @foreach ($frequencies as $frequency)
                            <option
                                value="{{ $frequency->value }}"
                                @selected(old('frequency', 'daily') === $frequency->value)
                            >
                                {{ ucfirst($frequency->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="run_time" class="block text-sm font-semibold text-slate-700">
                        Local run time
                    </label>

                    <input
                        id="run_time"
                        name="run_time"
                        type="time"
                        required
                        value="{{ old('run_time', '08:00') }}"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                    >
                </div>

                <div>
                    <label for="timezone" class="block text-sm font-semibold text-slate-700">
                        Reporting timezone
                    </label>

                    <input
                        id="timezone"
                        name="timezone"
                        type="text"
                        list="timezone-options"
                        required
                        value="{{ old('timezone', 'Europe/Luxembourg') }}"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                    >

                    <datalist id="timezone-options">
                        @foreach ($timezones as $timezone)
                            <option value="{{ $timezone }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="weekday" class="block text-sm font-semibold text-slate-700">
                        Weekday
                    </label>

                    <select
                        id="weekday"
                        name="weekday"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                    >
                        <option value="">Only for weekly schedules</option>

                        @foreach ([
                            1 => 'Monday',
                            2 => 'Tuesday',
                            3 => 'Wednesday',
                            4 => 'Thursday',
                            5 => 'Friday',
                            6 => 'Saturday',
                            7 => 'Sunday',
                        ] as $number => $label)
                            <option
                                value="{{ $number }}"
                                @selected((string) old('weekday') === (string) $number)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="day_of_month" class="block text-sm font-semibold text-slate-700">
                        Day of month
                    </label>

                    <select
                        id="day_of_month"
                        name="day_of_month"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                    >
                        <option value="">Only for monthly schedules</option>

                        @for ($day = 1; $day <= 28; $day++)
                            <option
                                value="{{ $day }}"
                                @selected((string) old('day_of_month') === (string) $day)
                            >
                                {{ $day }}
                            </option>
                        @endfor
                    </select>
                </div>
            </div>

            <p class="rounded-lg bg-blue-50 p-4 text-sm text-blue-800">
                Weekly schedules require a weekday. Monthly schedules require a
                day from 1 to 28. Daily schedules require neither.
            </p>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-6">
                <a
                    href="{{ route('analytics.saved-reports.index') }}"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                >
                    Create schedule
                </button>
            </div>
        </form>
    </section>
@endsection
