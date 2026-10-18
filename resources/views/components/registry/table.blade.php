@props(['headings' => [], 'empty' => 'No records yet.'])

<div class="w-full overflow-x-auto">
    <table class="w-full text-left text-xs">
        <thead>
            <tr class="border-b border-base-300">
                @foreach ($headings as $heading)
                    <th class="px-3 py-2 font-sans text-xs font-semibold uppercase tracking-wide text-base-content/60">{{ $heading }}</th>
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
