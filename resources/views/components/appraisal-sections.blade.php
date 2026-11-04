@props(['active', 'staff' => null])

@php
    use App\Support\Appraisal;

    // The same rail the staff wizard uses, so the two modules read alike.
    $step_base = 'shrink-0 whitespace-nowrap rounded-lg border py-2 px-3 text-sm transition-all';
    $default_classes = $step_base . ' border-base-300 bg-white hover:border-[#2E7D32] hover:text-[#2E7D32]';
    $active_classes = $step_base . ' border-[#2E7D32] bg-[#2E7D32] text-white shadow-md hover:bg-[#256628]';
    // Nothing to open until there is an officer to open it on.
    $closed_classes = $step_base . ' border-base-200 bg-base-100 text-base-content/40 cursor-not-allowed';

    $active = Appraisal::resolve($active);
    $position = Appraisal::position($active);
    $open = $staff !== null;
@endphp

{{-- Which part of the report is open, and how far through it that is. --}}
<div class="mb-3 flex flex-wrap items-center justify-between gap-x-6 gap-y-2">
    <p class="m-0 text-sm text-base-content/60">
        @if ($open)
            Appraisal for
            <span class="font-semibold text-[#1B5E20]">{{ $staff->surname }}, {{ $staff->firstname }}</span>
            <span class="text-base-content/50">({{ $staff->staff_number }})</span>
        @else
            <span class="text-base-content/50">Choose an officer above to open the report.</span>
        @endif
    </p>

    @if ($open)
        <div class="flex min-w-[12rem] flex-1 items-center gap-2 sm:max-w-xs">
            <div class="h-1.5 flex-1 rounded-full bg-base-200">
                <div class="h-1.5 rounded-full bg-[#2E7D32]"
                     style="width: {{ round($position / Appraisal::count() * 100) }}%"></div>
            </div>
            <p class="m-0 whitespace-nowrap text-xs text-base-content/50">
                {{ $position }} of {{ Appraisal::count() }}
            </p>
        </div>
    @endif
</div>

{{-- A horizontal rail: it scrolls sideways once the sections outrun the
     width, which thirteen of them always do. --}}
<nav aria-label="Appraisal sections" data-appraisal-sections
     class="-mx-1 overflow-x-auto overscroll-x-contain scroll-smooth px-1 pb-1">
    <div class="flex w-max items-stretch gap-2">
        @foreach (Appraisal::SECTIONS as $slug => $label)
            @php
                $isActive = $open && $slug === $active;
                $classes = ! $open ? $closed_classes : ($isActive ? $active_classes : $default_classes);
            @endphp

            {{-- A section is a link only once there is an officer to open it
                 on; until then it is a static row, the way the staff wizard
                 renders a category it will not let you reopen. --}}
            <{{ $open ? 'a' : 'div' }}
                @if ($open) href="{{ route('hr-appraisal', ['section' => $slug, 'staff' => $staff->ulid]) }}" @endif
                @if ($isActive) data-active-section aria-current="step" @endif
                @unless ($open) aria-disabled="true" title="Choose an officer first" @endunless
                class="font-sans flex items-center justify-start gap-2 {{ $classes }}">

                {{-- Numbered rather than iconed: thirteen sections of one
                     report read as an order, not as thirteen things. --}}
                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-semibold
                             {{ $isActive ? 'bg-[#1B5E20] text-white' : 'bg-base-200 text-base-content/50' }}">
                    {{ $loop->iteration }}
                </span>

                <span>{{ $label }}</span>
            </{{ $open ? 'a' : 'div' }}>
        @endforeach
    </div>
</nav>

<script>
    // When the rail overflows, bring the open section into view so you can
    // see where you are without scrolling to find it.
    document.querySelectorAll('[data-appraisal-sections]').forEach(function (rail) {
        const active = rail.querySelector('[data-active-section]');

        if (! active || rail.scrollWidth <= rail.clientWidth) {
            return;
        }

        rail.scrollLeft = active.offsetLeft - (rail.clientWidth - active.offsetWidth) / 2;
    });
</script>
