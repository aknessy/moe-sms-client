<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Performance Appraisal" crumb="Human Resources">

            @php
                // Nothing here is an officer's to open until the Registry has
                // put a list up for the year, and a self-service account has
                // nothing to open until they are on it themselves — either
                // way the page beneath is locked rather than merely empty.
                $locked = ! $briefPrepared || ($selfService && $staff === null);
            @endphp

            <div class="relative">
                @if ($locked)
                    <div class="absolute inset-0 z-10 flex items-center justify-center rounded-md bg-white/85 backdrop-blur-[1px]">
                        <div class="max-w-md rounded-md border border-base-300 bg-white px-6 py-5 text-center shadow-md">
                            @if (! $briefPrepared)
                                <p class="m-0 text-sm font-medium text-base-content">
                                    No {{ $briefYear }} Promotion Brief has been prepared.
                                </p>
                                <p class="mt-2 mb-0 text-xs text-base-content/60">
                                    Performance Appraisal only opens on the officers the
                                    Registry has put up for promotion that year. Prepare
                                    the {{ $briefYear }} brief before appraising anyone.
                                </p>
                            @else
                                <p class="m-0 text-sm font-medium text-base-content">
                                    You are not on the {{ $briefYear }} Promotion Brief.
                                </p>
                                <p class="mt-2 mb-0 text-xs text-base-content/60">
                                    Performance Appraisal only opens on the officers the
                                    Registry has put up for promotion that year, so there
                                    is nothing open on you yet.
                                </p>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="{{ $locked ? 'pointer-events-none select-none opacity-40' : '' }}">
                    {{-- Who the report is on comes first: there is nothing to
                         open until an officer has been chosen. A self-service
                         account has nobody to choose, so it shows no card here. --}}
                    @unless ($selfService)
                        <x-registry.card title="Select Staff"
                                         description="The officer this Annual Performance Evaluation Report is on">
                            {{-- No section travels with this: a report opens at the
                                 beginning, whatever section happened to be showing for
                                 whoever was chosen before. --}}
                            <form method="GET" action="{{ route('hr-appraisal') }}"
                                  class="flex w-full flex-col gap-4 py-3 sm:flex-row sm:items-end">

                                {{-- min-w-0 so the picker gives way rather than pushing the
                                     buttons past the edge of the card. --}}
                                <div class="min-w-0 flex-1">
                                    <x-registry.picker name="staff" label="Staff"
                                                       description="Search by name, file or computer number. They stay chosen as you move between sections."
                                                       :options="$staffOptions" :value="$selected" required />
                                </div>

                                <div class="flex shrink-0 flex-wrap items-center gap-2 pb-0.5">
                                    <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Open Report</button>
                                    @if ($staff)
                                        <a href="{{ route('hr-appraisal') }}"
                                           class="btn btn-sm btn-cta-outline px-4">Clear</a>
                                    @endif
                                </div>
                            </form>
                        </x-registry.card>
                    @endunless

                    {{-- Which year this report is being read against. Self
                         Reporting is the only section that holds anything by
                         year, but the officer stays the same across all of
                         them, so the dropdown sits here rather than inside
                         just that one section. --}}
                    @if ($staff)
                        <x-registry.card title="Select Year"
                                         description="The year of Self Reporting activity this report is being read against.">
                            <form method="GET" action="{{ route('hr-appraisal') }}" class="w-full py-3">
                                <input type="hidden" name="staff" value="{{ $staff->ulid }}" />
                                <input type="hidden" name="section" value="{{ $section }}" />

                                <label for="appraisal-year" class="font-sans text-xs font-medium text-base-content">Year under review</label>
                                <select id="appraisal-year" name="year" onchange="this.form.submit()"
                                        class="mt-1 w-full cursor-pointer rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 focus:border-primary focus:outline-none hover:border-primary/50">
                                    @foreach ($reportingWindow->years as $year)
                                        <option value="{{ $year }}" @selected($year === $selectedYear)>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </x-registry.card>
                    @endif

                    {{-- The sections of the report. Closed until there is an officer
                         to open them on. --}}
                    <div class="w-full mb-4 bg-white px-4 py-3 border border-base-300 rounded-md">
                        <x-appraisal-sections :active="$section" :staff="$staff" />
                    </div>

                    <x-registry.card :title="$sectionLabel"
                                     :description="$staff
                                        ? ($selfService
                                            ? ''
                                            : 'For ' . trim($staff->surname . ', ' . trim($staff->firstname . ' ' . $staff->othernames)))
                                        : 'Choose an officer above to begin'">

                        @if ($staff === null)
                            <div class="py-12 text-center">
                                <p class="m-0 text-sm text-base-content/60">
                                    Choose the officer this report is on.
                                </p>
                            </div>
                        @else
                            {{-- A section renders its own partial where it has one,
                                 and says so plainly where it has not. --}}
                            @if ($readOnly)
                                <p class="mb-0 mt-3 rounded-md bg-base-200 px-3 py-2 text-xs text-base-content/70">
                                    @if (\App\Support\ReportingAuthority::isGapDelegate(auth()->user()))
                                        You are reading another officer's report. Signing and acting on it is HOD GAP's.
                                    @else
                                        You are reading another officer's report. The only part you act on is
                                        Suggestions (By Superior Officer).
                                    @endif
                                </p>
                            @endif

                            {{-- Disabled wholesale rather than section by section: the
                                 one HOD GAP may act on stays open. --}}
                            <fieldset class="m-0 min-w-0 border-0 p-0" @disabled($readOnly && $section !== 'suggestions')>
                                @includeFirst([
                                    'human-resources.appraisal.' . $section,
                                    'human-resources.appraisal.not-built',
                                ])
                            </fieldset>

                            {{-- A section with a form of its own carries its own way
                                 onward, saving as it goes. The rest only move. --}}
                            @if (! $sectionHasForm && $continueLabel !== null)
                                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
                                    <a href="{{ route('hr-appraisal', ['section' => $nextSection, 'staff' => $staff->ulid]) }}"
                                       class="btn btn-sm btn-cta px-4 shadow-md">{{ $continueLabel }}</a>
                                </div>
                            @endif
                        @endif
                    </x-registry.card>
                </div>
            </div>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
