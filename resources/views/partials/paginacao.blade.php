@if ($paginator->hasPages())
    <nav class="paginacao" aria-label="Páginas">
        @if ($paginator->onFirstPage())
            <span class="btn sm" aria-disabled="true"><x-icone nome="left" />Anterior</span>
        @else
            <a class="btn sm" href="{{ $paginator->previousPageUrl() }}" rel="prev"><x-icone nome="left" />Anterior</a>
        @endif
        <span class="small muted">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a class="btn sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Próxima<x-icone nome="right" /></a>
        @else
            <span class="btn sm" aria-disabled="true">Próxima<x-icone nome="right" /></span>
        @endif
    </nav>
@endif
