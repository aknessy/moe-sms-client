@props(['title', 'description' => '', 'count' => null])

<div class="w-full mb-4 px-4 py-2 bg-white border border-base-300 rounded-lg">
    <div class="flex flex-col items-start border-b border-base-300 py-1">
        <div class="flex w-full items-center justify-between">
            <h2 class="font-alkalami text-base font-semibold mb-0 text-base-content/80">{{ $title }}</h2>
            @isset($count)
                <span class="text-xs text-base-content/60">{{ $count }} record{{ $count == 1 ? '' : 's' }}</span>
            @endisset
        </div>
        @if ($description)
            <p class="font-sans text-sm font-normal text-base-content/60 m-0">{{ $description }}</p>
        @endif
    </div>
    <div class="py-4">
        {{ $slot }}
    </div>
</div>
