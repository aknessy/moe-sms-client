<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Performance Appraisal" crumb="Human Resources">

            <div class="relative">
                {{-- Nothing here is an officer's to open until the Registry
                     has put a list up for the year — the overlay says so
                     and the page beneath it is locked rather than merely
                     empty. --}}
                @unless ($briefPrepared)
                    <div class="absolute inset-0 z-10 flex items-center justify-center rounded-md bg-white/85 backdrop-blur-[1px]">
                        <div class="max-w-md rounded-md border border-base-300 bg-white px-6 py-5 text-center shadow-md">
                            <p class="m-0 text-sm font-medium text-base-content">
                                No {{ $briefYear }} Promotion Brief has been prepared.
                            </p>
                            <p class="mt-2 mb-0 text-xs text-base-content/60">
                                Performance Appraisal only opens on the officers the
                                Registry has put up for promotion that year. Prepare
                                the {{ $briefYear }} brief before appraising anyone.
                            </p>
                        </div>
                    </div>
                @endunless

                <div class="{{ $briefPrepared ? '' : 'pointer-events-none select-none opacity-40' }}">
                    {{-- Who the report is on comes first: there is nothing to
                         open until an officer has been chosen. --}}
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

                    {{-- The sections of the report. Closed until there is an officer
                         to open them on. --}}
                    <div class="w-full mb-4 bg-white px-4 py-3 border border-base-300 rounded-md">
                        <x-appraisal-sections :active="$section" :staff="$staff" />
                    </div>

                    <x-registry.card :title="$sectionLabel"
                                     :description="$staff
                                        ? 'For ' . trim($staff->surname . ', ' . trim($staff->firstname . ' ' . $staff->othernames))
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
                            @includeFirst([
                                'human-resources.appraisal.' . $section,
                                'human-resources.appraisal.not-built',
                            ])

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
