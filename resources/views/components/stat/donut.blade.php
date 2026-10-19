@props(['segments' => [], 'centreLabel' => 'Total'])

@php
    // Plain SVG: each slice is a dashed arc offset by the ones before it.
    $total = collect($segments)->sum('value');
    $radius = 54;
    $circumference = 2 * M_PI * $radius;
    $offset = 0;
@endphp

<div class="flex flex-col items-center gap-5 sm:flex-row sm:items-center sm:justify-center">
    <svg viewBox="0 0 140 140" class="h-40 w-40 shrink-0" role="img"
         aria-label="{{ collect($segments)->map(fn ($s) => $s['label'] . ': ' . $s['value'])->implode(', ') }}">
        {{-- Only the arcs are rotated, so they start at twelve o'clock while the
             centre labels stay upright. --}}
        <g transform="rotate(-90 70 70)">
            <circle cx="70" cy="70" r="{{ $radius }}" fill="none" stroke="currentColor"
                    class="text-base-300" stroke-width="16" />

            @foreach ($segments as $segment)
                @if ($total > 0 && $segment['value'] > 0)
                    @php
                        $length = ($segment['value'] / $total) * $circumference;
                    @endphp
                    <circle cx="70" cy="70" r="{{ $radius }}" fill="none"
                            stroke="{{ $segment['colour'] }}" stroke-width="16"
                            stroke-dasharray="{{ $length }} {{ $circumference - $length }}"
                            stroke-dashoffset="{{ -$offset }}" />
                    @php $offset += $length; @endphp
                @endif
            @endforeach
        </g>

        <text x="70" y="68" text-anchor="middle" class="fill-base-content" font-size="20" font-weight="600">{{ $total }}</text>
        <text x="70" y="84" text-anchor="middle" class="fill-base-content/50" font-size="7" letter-spacing="0.5">{{ strtoupper($centreLabel) }}</text>
    </svg>

    <ul class="m-0 w-full list-none space-y-2 p-0 sm:w-auto">
        @foreach ($segments as $segment)
            <li class="flex items-center justify-between gap-6 text-xs">
                <span class="flex items-center gap-2 text-base-content/70">
                    <span class="inline-block h-2.5 w-2.5 rounded-full" style="background: {{ $segment['colour'] }}"></span>
                    {{ $segment['label'] }}
                </span>
                <span class="font-medium text-base-content">
                    {{ $segment['value'] }}
                    <span class="text-base-content/50">({{ $total > 0 ? round($segment['value'] / $total * 100) : 0 }}%)</span>
                </span>
            </li>
        @endforeach
    </ul>
</div>
