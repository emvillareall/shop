<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Booty Fitness') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen font-['Manrope']">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="flex h-16 items-center justify-between px-4 md:px-6">
                <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-slate-200 text-slate-700 md:hidden" @click="sidebarOpen = true">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <a href="{{ route('home') }}" class="hidden items-center gap-3 md:flex">
                    <img src="{{ asset('imagenes/logotipo.png') }}" alt="Booty Fitness" class="h-10 w-10 rounded-full object-cover ring-2 ring-brand-200">
                    <span class="text-lg font-bold tracking-tight text-slate-900">{{ config('app.name') }}</span>
                </a>

                <div class="ml-auto flex items-center gap-3">
                    @auth
                        <div class="hidden text-right md:block">
                            <p class="text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-slate-500">Miembro desde {{ Auth::user()->created_at->format('M Y') }}</p>
                        </div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-dark">
                                <i class="fa-solid fa-right-from-bracket mr-1"></i>Salir
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </header>

        <div class="flex min-h-[calc(100vh-4rem)]">
            <div class="fixed inset-0 z-40 bg-slate-900/50 md:hidden" x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"></div>

            <aside class="fixed bottom-0 left-0 top-16 z-50 w-72 -translate-x-full overflow-y-auto border-r border-brand-200 bg-brand-50 px-4 py-5 transition-transform md:hidden" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
                <div class="mb-6 flex items-center justify-between md:hidden">
                    <span class="text-base font-bold text-brand-800">{{ config('app.name') }}</span>
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-brand-200 text-brand-700" @click="sidebarOpen = false">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                @include('layouts.sidebar')
            </aside>

            <aside class="hidden w-72 shrink-0 border-r border-brand-200 bg-brand-50 md:block">
                <div class="sticky top-16 h-[calc(100vh-4rem)] overflow-y-auto px-4 py-5">
                    @include('layouts.sidebar')
                </div>
            </aside>

            <main class="w-full min-w-0 flex-1 px-4 py-5 md:px-6">
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
    @yield('scripts')
</body>
</html>
