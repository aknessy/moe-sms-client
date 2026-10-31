<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Performance Appraisal" crumb="Human Resources">

            {{-- Who the report is on comes first: there is nothing to open
                 until an officer has been chosen. --}}
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
                <div class="py-12 text-center">
                    <p class="m-0 text-sm text-base-content/60">
                        {{ $staff
                            ? 'This section has not been built yet.'
                            : 'Choose the officer this report is on.' }}
                    </p>
                </div>
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
