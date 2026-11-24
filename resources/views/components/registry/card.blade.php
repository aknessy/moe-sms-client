@props(['title', 'description' => '', 'count' => null, 'headerClass' => ''])

{{-- A card with a headerClass has a coloured band across its top, edge to
     edge, so the text on it goes light. --}}
@php($tinted = filled($headerClass))

<div class="w-full mb-4 px-4 py-2 bg-white border border-base-300 rounded-lg">
    <div class="flex flex-col items-start border-b border-base-300 py-1 {{ $tinted ? '-mx-4 -mt-2 mb-0 rounded-t-lg px-4 pt-3 pb-2 ' . $headerClass : '' }}">
        <div class="flex w-full items-center justify-between">
            <h2 class="font-alkalami text-base font-semibold mb-0 {{ $tinted ? 'text-white' : 'text-base-content/80' }}">{{ $title }}</h2>
            @isset($count)
                <span class="text-xs {{ $tinted ? 'text-white/80' : 'text-base-content/60' }}">{{ $count }} record{{ $count == 1 ? '' : 's' }}</span>
            @endisset
            {{-- Something to act on the card with, in the count's place. --}}
            @isset($action)
                <div>{{ $action }}</div>
            @endisset
        </div>
        @if ($description)
            <p class="font-sans text-sm font-normal m-0 {{ $tinted ? 'text-white/80' : 'text-base-content/60' }}">{{ $description }}</p>
        @endif
    </div>
    <div class="py-4">
        {{ $slot }}
    </div>
</div>
