@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-wrap items-center gap-1">
        @if ($paginator->onFirstPage())
            <span class="btn btn-outline btn-sm cursor-not-allowed opacity-50" aria-disabled="true">
                <x-icon name="chevron-right" class="h-4 w-4 rotate-180" />
                Sebelumnya
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-outline btn-sm">
                <x-icon name="chevron-right" class="h-4 w-4 rotate-180" />
                Sebelumnya
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-2 text-sm text-slate-400">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="btn btn-primary btn-sm min-w-9">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="btn btn-outline btn-sm min-w-9"
                           aria-label="Halaman {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-outline btn-sm">
                Berikutnya
                <x-icon name="chevron-right" class="h-4 w-4" />
            </a>
        @else
            <span class="btn btn-outline btn-sm cursor-not-allowed opacity-50" aria-disabled="true">
                Berikutnya
                <x-icon name="chevron-right" class="h-4 w-4" />
            </span>
        @endif
    </nav>
@endif
