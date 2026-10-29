<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Ad-hoc Staff" crumb="ICT/Voter Registry" description="Election-period recruitment and deployment" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
