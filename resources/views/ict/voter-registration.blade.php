<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Voter Registration" crumb="ICT/Voter Registry" description="Continuous voter registration records" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
