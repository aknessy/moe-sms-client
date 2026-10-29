@props([
    'headings' => [],
    'empty' => 'No records yet.',
    // Set on tables with more columns than fit. The table is then sized to its
    // content rather than the page, and cells stop wrapping, so the columns
    // keep their width and the table scrolls sideways instead.
    'wide' => false,
])

<div class="w-full overflow-x-auto {{ $wide ? '[&_td]:whitespace-nowrap' : '' }}">
    <table class="{{ $wide ? 'min-w-max' : 'w-full' }} text-left text-xs">
        {{-- A darker shade of the theme green, so the header reads as a band
             rather than as another row. --}}
        <thead>
            <tr class="bg-[#1B5E20]">
                @foreach ($headings as $heading)
                    <th class="px-3 py-2 font-sans text-xs font-semibold uppercase tracking-wide text-white {{ $wide ? 'whitespace-nowrap' : '' }}">{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-base-300/70">
            {{ $slot }}
        </tbody>
    </table>

    @if (trim($slot) === '')
        <p class="py-10 text-center text-sm text-base-content/60 m-0">{{ $empty }}</p>
    @endif
</div>
