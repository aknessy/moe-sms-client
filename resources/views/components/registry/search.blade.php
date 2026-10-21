@props(['action', 'value' => null, 'placeholder' => 'Search', 'total' => null])

{{-- Searching happens in SQL so it covers the whole result set rather than the
     rows that happen to be on screen. --}}
<form method="GET" action="{{ $action }}" class="mb-3 flex flex-wrap items-center justify-between gap-2 print:hidden">
    <div class="flex flex-wrap items-center gap-2">
        <div class="relative">
            <input type="search" name="q" value="{{ $value }}" placeholder="{{ $placeholder }}"
                   class="w-full min-w-[18rem] bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md pl-3 pr-9 py-2 focus:outline-none focus:border-primary hover:border-primary/50" />
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                 class="pointer-events-none absolute w-4 h-4 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"></path>
                <path d="M21 21l-6 -6"></path>
            </svg>
        </div>

        <button type="submit" class="btn btn-sm btn-cta px-4">Search</button>

        @if (filled($value))
            <a href="{{ $action }}" class="text-xs text-base-content/60 hover:text-[#2E7D32]">Clear</a>
            <span class="text-xs text-base-content/50">{{ $total }} match{{ $total == 1 ? '' : 'es' }}</span>
        @endif
    </div>

    {{ $slot ?? '' }}
</form>
