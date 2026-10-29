<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Establishment Strength" crumb="Human Resources" description="Approved positions against filled positions, by cadre" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
