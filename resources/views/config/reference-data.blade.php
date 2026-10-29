<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Reference Data" crumb="Administration" description="Departments, grade levels, designations, states and LGAs" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
