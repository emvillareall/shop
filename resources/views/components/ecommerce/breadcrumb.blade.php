@props(['items' => []])

<nav class="mb-4 text-sm text-slate-500">
    <ol class="flex flex-wrap items-center gap-2">
        @foreach($items as $item)
            <li class="flex items-center gap-2">
                @if(!empty($item['url']))
                    <a href="{{ $item['url'] }}" class="hover:text-brand-700">{{ $item['label'] }}</a>
                @else
                    <span class="font-medium text-slate-700">{{ $item['label'] }}</span>
                @endif
                @if(!$loop->last)
                    <span>/</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
