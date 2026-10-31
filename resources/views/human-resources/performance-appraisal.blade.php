<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Performance Appraisal" crumb="Human Resources">

            {{-- The sections of the report, and who it is on. The two sit
                 together because both belong to the whole report rather than
                 to any one section of it. --}}
            <div class="w-full mb-4 bg-white px-4 py-3 border border-base-300 rounded-md">
                <x-appraisal-sections :active="$section" :staff="$staff" />

                <form method="GET" action="{{ route('hr-appraisal') }}"
                      class="mt-3 flex w-full flex-col gap-4 border-t border-base-200 pt-3 lg:flex-row lg:items-end">

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
