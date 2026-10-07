<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Cobros | Booty Fitness' }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('bootyfitness-icons/bootyfitness-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('bootyfitness-icons/bootyfitness-180.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#64397d">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f4fa] text-slate-800">
    <main class="mx-auto flex min-h-screen w-full max-w-xl items-center px-4 py-4 sm:px-6 sm:py-5">
        <div class="w-full">
            @if(session('success') && !($accessView ?? false))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    @stack('scripts')
</body>
</html>
