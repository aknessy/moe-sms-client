<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Performance Appraisal" crumb="Human Resources">

            {{-- The sections of the report, and where in them you are. --}}
            <div class="w-full mb-4 bg-white px-4 py-3 border border-base-300 rounded-md">
                <x-appraisal-sections :active="$section" :staff="$staff" />
            </div>

            {{-- Who the report is on. Full width, so it reads as belonging to
                 the whole report rather than to the section below it. --}}
            <x-registry.card title="Select Staff"
                             description="The officer this Annual Performance Evaluation Report is on">
                <form method="GET" action="{{ route('hr-appraisal') }}"
                      class="flex flex-col gap-4 lg:flex-row lg:items-end">

                    {{-- Keeps the section you are on when the officer changes. --}}
                    <input type="hidden" name="section" value="{{ $section }}" />

                    <x-registry.picker name="staff" label="Staff"
                                       :options="$staffOptions" :value="$selected" required />

                    <div class="flex flex-wrap items-center gap-2 pb-0.5">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Open Report</button>
                        @if ($staff)
                            <a href="{{ route('hr-appraisal', ['section' => $section]) }}"
                               class="btn btn-sm btn-cta-outline px-4">Clear</a>
                        @endif
                    </div>
                </form>
            </x-registry.card>

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
