<a href="{{ route('home') }}" class="mb-6 flex items-center gap-3 rounded-lg bg-white/80 p-3 shadow-sm">
    <img src="{{ asset('imagenes/logotipo.png') }}" alt="Booty Fitness" class="h-12 w-12 rounded-full object-cover">
    <div>
        <p class="text-sm font-bold uppercase tracking-wide text-brand-800">{{ config('app.name') }}</p>
        <p class="text-xs text-slate-600">Panel de administración</p>
    </div>
</a>

<nav class="space-y-1">
    @include('layouts.menu')
</nav>
