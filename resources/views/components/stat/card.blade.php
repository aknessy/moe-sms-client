@props(['label', 'value', 'hint' => null, 'tone' => 'default', 'href' => null])

@php
    $tones = [
        'default' => 'text-base-content',
        'green' => 'text-[#1B5E20]',
        'warning' => 'text-warning-content',
        'error' => 'text-error',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
    class="block w-full rounded-lg border border-base-300 bg-white px-4 py-4 transition-shadow {{ $href ? 'hover:shadow-md' : '' }}">
    <p class="m-0 text-xs uppercase tracking-wide text-base-content/60">{{ $label }}</p>
    <p class="mt-1 mb-0 font-alkalami text-2xl font-semibold {{ $tones[$tone] ?? $tones['default'] }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 mb-0 text-xs text-base-content/50">{{ $hint }}</p>
    @endif
</{{ $tag }}>
