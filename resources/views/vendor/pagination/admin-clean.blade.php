@if ($paginator->hasPages())
    <nav class="d-flex justify-content-center align-items-center py-2" role="navigation" aria-label="Paginacion">
        <ul class="mb-0 ps-0"
            style="list-style:none;display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;justify-content:center;">
            @if ($paginator->onFirstPage())
                <li aria-disabled="true" aria-label="Anterior">
                    <span aria-hidden="true"
                        style="display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 .75rem;border:1px solid #d1d9e6;border-radius:.65rem;background:#eef2f7;color:#8a97ad;cursor:not-allowed;">&lsaquo;</span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior"
                        style="display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 .75rem;border:1px solid #c7d0e0;border-radius:.65rem;background:#fff;color:#364760;text-decoration:none;">&lsaquo;</a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li aria-disabled="true">
                        <span
                            style="display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 .75rem;border:1px solid #d1d9e6;border-radius:.65rem;background:#eef2f7;color:#8a97ad;">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li aria-current="page">
                                <span
                                    style="display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 .75rem;border:1px solid #7a44ae;border-radius:.65rem;background:#7a44ae;color:#fff;font-weight:700;">{{ $page }}</span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $url }}"
                                    style="display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 .75rem;border:1px solid #c7d0e0;border-radius:.65rem;background:#fff;color:#364760;text-decoration:none;">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Siguiente"
                        style="display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 .75rem;border:1px solid #c7d0e0;border-radius:.65rem;background:#fff;color:#364760;text-decoration:none;">&rsaquo;</a>
                </li>
            @else
                <li aria-disabled="true" aria-label="Siguiente">
                    <span aria-hidden="true"
                        style="display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 .75rem;border:1px solid #d1d9e6;border-radius:.65rem;background:#eef2f7;color:#8a97ad;cursor:not-allowed;">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
