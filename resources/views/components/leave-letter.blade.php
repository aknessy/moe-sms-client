@props(['letter'])

@php
    $head = $letter->letterhead();
    $chain = $letter->chain();
    $signatory = $letter->signatory();
@endphp

{{-- The application as it would read on paper. Sized to a sheet and ruled
     like one, so what is on screen is what comes out of the printer. --}}
<div class="mx-auto w-full max-w-3xl bg-white px-10 py-10 text-base-content shadow-sm print:max-w-none print:px-0 print:shadow-none"
     data-letter>

    {{-- Letterhead --}}
    <header class="border-b-2 border-base-content pb-3 text-center">
        <h1 class="m-0 font-alkalami text-xl font-bold uppercase tracking-wide">{{ $head['name'] }}</h1>

        @if (filled($head['command']))
            <p class="m-0 text-sm font-semibold uppercase tracking-wide">{{ $head['command'] }}</p>
        @endif

        @if (filled($head['address']))
            <p class="mt-1 mb-0 whitespace-pre-line text-xs text-base-content/70">{{ $head['address'] }}</p>
        @endif

        <p class="mt-1 mb-0 text-xs text-base-content/70">
            @if (filled($head['telephone']))
                <span>Tel: {{ $head['telephone'] }}</span>
            @endif
            @if (filled($head['telephone']) && filled($head['email']))
                <span class="px-1">&middot;</span>
            @endif
            @if (filled($head['email']))
                <span>{{ $head['email'] }}</span>
            @endif
        </p>
    </header>

    {{-- Where the officer writes from, and the day they wrote. --}}
    <div class="mt-6 flex items-start justify-between gap-6 text-sm">
        <address class="not-italic leading-relaxed">
            @foreach ($letter->origin() as $line)
                <span class="block">{{ $line }}</span>
            @endforeach
        </address>
        <p class="m-0 shrink-0 text-right">{{ $letter->dated() }}</p>
    </div>

    {{-- Addressed up the chain. --}}
    <div class="mt-6 text-sm leading-relaxed">
        <p class="m-0 font-semibold">{{ $chain['addressee'] }},</p>
        <p class="m-0">{{ $head['name'] }},</p>
        @if (filled($head['command']))
            <p class="m-0">{{ $head['command'] }}.</p>
        @endif

        @foreach ($chain['through'] as $desk)
            <p class="mt-2 mb-0">
                <span class="italic">Through:</span> {{ $desk }},
            </p>
        @endforeach
    </div>

    {{-- What it is about. --}}
    <h2 class="mt-8 mb-0 text-center text-sm font-bold uppercase underline">
        {{ $letter->heading() }}
    </h2>

    <div class="mt-6 space-y-4 text-sm leading-7 text-justify">
        @foreach ($letter->body() as $paragraph)
            <p class="m-0">{{ $paragraph }}</p>
        @endforeach
    </div>

    {{-- Who signs it. The rule is signed by hand, which is why it is a rule
         and not the name printed twice. --}}
    <div class="mt-10 text-sm">
        <p class="m-0">Yours faithfully,</p>

        <div class="mt-10 w-72">
            <div class="border-b border-base-content"></div>
            <p class="mt-1 mb-0 font-semibold">{{ $signatory['name'] }}</p>
            @if (filled($signatory['rank']))
                <p class="m-0 text-xs text-base-content/70">{{ $signatory['rank'] }}</p>
            @endif
            <p class="m-0 text-xs text-base-content/70">
                {{ $signatory['grade'] }}
                @if (filled($signatory['staff_number']))
                    &middot; {{ $signatory['staff_number'] }}
                @endif
            </p>
        </div>
    </div>
</div>
