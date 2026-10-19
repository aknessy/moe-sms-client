@props([
    'groups' => [],      // [['label' => '2025', 'bars' => [['label' =>, 'value' =>, 'colour' =>], ...]], ...]
    'height' => 180,
])

@php
    $max = collect($groups)->flatMap(fn ($g) => collect($g['bars'])->pluck('value'))->max();
    $max = $max && $max > 0 ? $max : 1;
    $series = collect($groups)->first()['bars'] ?? [];
@endphp

<div>
    <div class="flex items-end justify-around gap-6" style="height: {{ $height }}px">
        @foreach ($groups as $group)
            <div class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                <div class="flex h-full w-full items-end justify-center gap-3">
                    @foreach ($group['bars'] as $bar)
                        <div class="flex h-full w-10 flex-col items-center justify-end">
                            <span class="mb-1 text-xs font-medium text-base-content">{{ $bar['value'] }}</span>
                            <div class="w-full rounded-t"
                                 style="height: {{ max(2, round($bar['value'] / $max * 100)) }}%; background: {{ $bar['colour'] }}"
                                 title="{{ $group['label'] }} — {{ $bar['label'] }}: {{ $bar['value'] }}"></div>
                        </div>
                    @endforeach
                </div>
                <span class="text-xs font-medium text-base-content/70">{{ $group['label'] }}</span>
            </div>
        @endforeach
    </div>

    <ul class="m-0 mt-4 flex list-none flex-wrap justify-center gap-4 p-0">
        @foreach ($series as $bar)
            <li class="flex items-center gap-2 text-xs text-base-content/70">
                <span class="inline-block h-2.5 w-2.5 rounded-full" style="background: {{ $bar['colour'] }}"></span>
                {{ $bar['label'] }}
            </li>
        @endforeach
    </ul>
</div>
