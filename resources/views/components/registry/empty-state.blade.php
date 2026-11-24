@props(['title', 'description' => ''])

{{-- What a list shows when there is nothing in it: an icon and a line saying
     so, rather than a bare "no records". Tinted with a light shade of the
     primary colour. The icon can be swapped by passing one in the slot. --}}
<div class="flex flex-col items-center justify-center rounded-lg bg-primary/5 px-4 py-10 text-center">
    <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-primary/15 text-primary">
        @if ($slot->isEmpty())
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" class="h-8 w-8" aria-hidden="true">
                <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z"></path>
                <path d="M16 3v4"></path>
                <path d="M8 3v4"></path>
                <path d="M4 11h16"></path>
                <path d="M10 15h4"></path>
            </svg>
        @else
            {{ $slot }}
        @endif
    </div>
    <p class="m-0 text-sm font-medium text-base-content/70">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 mb-0 max-w-sm text-xs text-base-content/50">{{ $description }}</p>
    @endif
</div>
