@props(['variant' => 'default'])

@php
    $styles = match($variant) {
        'success' => 'bg-emerald-100 text-emerald-700',
        'danger' => 'bg-rose-100 text-rose-700',
        'warning' => 'bg-amber-100 text-amber-700',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2 py-1 text-xs font-semibold {$styles}"]) }}>
    {{ $slot }}
</span>
