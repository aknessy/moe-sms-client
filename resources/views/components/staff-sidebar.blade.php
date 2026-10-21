@props(['activePage', 'completed' => [], 'draft' => null])

@php
    // Active item picks up the main sidebar's green; the icon sits on a darker
    // shade of it so it stays readable against the pill.
    $svg_classes = 'rounded-full w-6 h-6 bg-[#1B5E20] text-white p-1 flex items-center justify-center';
    $step_base = 'shrink-0 whitespace-nowrap rounded-lg border py-2 px-3 text-sm transition-all';
    $default_page_classes = $step_base . ' border-base-300 bg-white hover:border-[#2E7D32] hover:text-[#2E7D32]';
    $active_page_classes = $step_base . ' border-[#2E7D32] bg-[#2E7D32] text-white shadow-md hover:bg-[#256628]';
    // Captured categories are closed until the whole record is finished.
    $done_page_classes = $step_base . ' border-[#2E7D32]/30 bg-[#2E7D32]/10 text-[#1B5E20] cursor-not-allowed';

    $steps = [
        ['key' => 'index', 'route' => 'basic-info', 'label' => 'Personal Information', 'paths' => [
            'M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0', 'M16 19h6', 'M19 16v6', 'M6 21v-2a4 4 0 0 1 4 -4h4',
        ]],
        ['key' => 'edu-progress', 'route' => 'edu-progress', 'label' => 'Educational Progression', 'paths' => [
            'M5 4m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z',
            'M9 4m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z',
            'M5 8h4', 'M9 16h4',
            'M13.803 4.56l2.184 -.53c.562 -.135 1.133 .19 1.282 .732l3.695 13.418a1.02 1.02 0 0 1 -.634 1.219l-.133 .041l-2.184 .53c-.562 .135 -1.133 -.19 -1.282 -.732l-3.695 -13.418a1.02 1.02 0 0 1 .634 -1.219l.133 -.041z',
            'M14 9l4 -1', 'M16 16l3.923 -.98',
        ]],
        ['key' => 'promotion', 'route' => 'promotion', 'label' => 'Promotion', 'paths' => [
            'M6 4h11a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-11a1 1 0 0 1 -1 -1v-14a1 1 0 0 1 1 -1m3 0v18',
            'M13 8l2 0', 'M13 12l2 0',
        ]],
        ['key' => 'career', 'route' => 'career', 'label' => 'Working Career', 'paths' => [
            'M9 5m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z',
            'M4 7l1 0', 'M4 11l1 0', 'M19 7l1 0', 'M19 11l1 0', 'M4 15l16 0', 'M4 19l16 0',
        ]],
        ['key' => 'leave-record', 'route' => 'leave-record', 'label' => 'Leave Record', 'paths' => [
            'M13 12v.01', 'M3 21h18', 'M5 21v-16a2 2 0 0 1 2 -2h7.5m2.5 10.5v7.5', 'M14 7h7m-3 -3l3 3l-3 3',
        ]],
        ['key' => 'referee', 'route' => 'referee', 'label' => 'Referee(s)', 'paths' => [
            'M4 6l5.5 0', 'M4 10l5.5 0', 'M4 14l5.5 0', 'M4 18l5.5 0',
            'M14.5 6l5.5 0', 'M14.5 10l5.5 0', 'M14.5 14l5.5 0', 'M14.5 18l5.5 0',
        ]],
        ['key' => 'next-of-kin', 'route' => 'next-of-kin', 'label' => 'Next of Kin', 'paths' => [
            'M6 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-14a1 1 0 0 1 1 -1z',
            'M15 12l4 0', 'M17 10l0 4',
        ]],
    ];

    $doneCount = count(array_intersect(array_column($steps, 'key'), $completed));
@endphp

@if ($draft)
    {{-- The draft banner reads across rather than down, to sit above the form. --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-x-6 gap-y-2 rounded-md border border-[#2E7D32]/30 bg-[#2E7D32]/5 px-3 py-2">
        <p class="m-0 text-sm text-base-content/60">
            Recording for
            <span class="font-semibold text-[#1B5E20]">{{ $draft->surname }}, {{ $draft->firstname }}</span>
            <span class="text-base-content/50">({{ $draft->staff_number }})</span>
        </p>

        <div class="flex min-w-[12rem] flex-1 items-center gap-2 sm:max-w-xs">
            <div class="h-1.5 flex-1 rounded-full bg-base-200">
                <div class="h-1.5 rounded-full bg-[#2E7D32]" style="width: {{ round($doneCount / count($steps) * 100) }}%"></div>
            </div>
            <p class="m-0 whitespace-nowrap text-xs text-base-content/50">{{ $doneCount }} of {{ count($steps) }} recorded</p>
        </div>
    </div>
@endif

{{-- A horizontal rail: it scrolls sideways once the steps outrun the width,
     which they always do on a phone. --}}
<nav aria-label="Record categories" data-staff-steps
     class="-mx-1 overflow-x-auto overscroll-x-contain scroll-smooth px-1 pb-1">
    <div class="flex w-max items-stretch gap-2">
    @foreach ($steps as $step)
        @php
            $isDone = in_array($step['key'], $completed, true);
            $isActive = $activePage === $step['key'];
            $classes = $isActive ? $active_page_classes : ($isDone ? $done_page_classes : $default_page_classes);
        @endphp

        {{-- A recorded category is rendered as a static row: it can't be reopened
             until every category has been captured for this staff member. --}}
        <{{ $isDone && ! $isActive ? 'div' : 'a' }}
            @if (! $isDone || $isActive) href="{{ route($step['route']) }}" @endif
            @if ($isActive) data-active-step aria-current="step" @endif
            @if ($isDone && ! $isActive) aria-disabled="true" title="Already recorded for this staff" @endif
            class="font-sans flex items-center justify-start gap-2 {{ $classes }}">

            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2"
                 class="h-5 w-5 shrink-0 {{ $isActive ? $svg_classes : '' }}">
                @foreach ($step['paths'] as $path)
                    <path d="{{ $path }}"></path>
                @endforeach
            </svg>

            <span>{{ $step['label'] }}</span>

            @if ($isDone)
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                     class="h-4 w-4 shrink-0 {{ $isActive ? 'text-white' : 'text-[#2E7D32]' }}"
                     role="img" aria-label="Recorded">
                    <path d="M12 2a10 10 0 1 0 0 20a10 10 0 0 0 0 -20zm4.28 7.47a1 1 0 0 0 -1.56 -1.25l-3.72 4.65l-1.79 -1.79a1 1 0 1 0 -1.42 1.42l2.58 2.58a1 1 0 0 0 1.49 -.08z"></path>
                </svg>
            @endif
        </{{ $isDone && ! $isActive ? 'div' : 'a' }}>
    @endforeach
    </div>
</nav>

<script>
    // When the rail overflows, bring the current category into view so the
    // user can see where they are without scrolling to find it.
    document.querySelectorAll('[data-staff-steps]').forEach(function (rail) {
        const active = rail.querySelector('[data-active-step]');

        if (! active || rail.scrollWidth <= rail.clientWidth) {
            return;
        }

        rail.scrollLeft = active.offsetLeft - (rail.clientWidth - active.offsetWidth) / 2;
    });
</script>
