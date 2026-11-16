{{-- Pagination aux couleurs du système de design (jetons sémantiques, mode sombre, cibles 44 px). --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
        <p class="text-sm text-muted">
            Page {{ $paginator->currentPage() }} sur {{ $paginator->lastPage() }}
        </p>
        <ul class="flex flex-wrap items-center gap-1">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="btn-secondary pointer-events-none opacity-50" aria-disabled="true">‹ <span class="hidden xs:inline">Précédent</span></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-secondary">‹ <span class="hidden xs:inline">Précédent</span></a>
                @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="inline-flex min-h-11 min-w-11 items-center justify-center text-muted" aria-hidden="true">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="hidden sm:block">
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="btn min-w-11 bg-ink px-3 text-page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn-ghost min-w-11 px-3" aria-label="Page {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-secondary"><span class="hidden xs:inline">Suivant</span> ›</a>
                @else
                    <span class="btn-secondary pointer-events-none opacity-50" aria-disabled="true"><span class="hidden xs:inline">Suivant</span> ›</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
