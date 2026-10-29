<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Pay Roll" crumb="Finance/Account" description="Monthly payroll runs and summaries" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>