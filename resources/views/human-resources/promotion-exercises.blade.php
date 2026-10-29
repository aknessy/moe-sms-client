<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Promotion Exercises" crumb="Human Resources" description="Eligibility lists and Senior Staff Committee outcomes" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
