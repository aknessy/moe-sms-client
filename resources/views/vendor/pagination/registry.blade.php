@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="mt-4 flex flex-col gap-3 border-t border-base-300 pt-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="m-0 text-xs text-base-content/60">
            Showing <span class="font-medium text-base-content">{{ $paginator->firstItem() }}</span>
            to <span class="font-medium text-base-content">{{ $paginator->lastItem() }}</span>
            of <span class="font-medium text-base-content">{{ $paginator->total() }}</span>
        </p>

        <div class="flex flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="rounded-md border border-base-300 px-2.5 py-1 text-xs text-base-content/30">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="rounded-md border border-base-300 px-2.5 py-1 text-xs text-base-content/70 hover:border-[#2E7D32] hover:text-[#2E7D32]">Previous</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-1.5 text-xs text-base-content/40">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="rounded-md border border-[#2E7D32] bg-[#2E7D32] px-2.5 py-1 text-xs font-medium text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}"
                               class="rounded-md border border-base-300 px-2.5 py-1 text-xs text-base-content/70 hover:border-[#2E7D32] hover:text-[#2E7D32]">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="rounded-md border border-base-300 px-2.5 py-1 text-xs text-base-content/70 hover:border-[#2E7D32] hover:text-[#2E7D32]">Next</a>
            @else
                <span class="rounded-md border border-base-300 px-2.5 py-1 text-xs text-base-content/30">Next</span>
            @endif
        </div>
    </nav>
@endif
