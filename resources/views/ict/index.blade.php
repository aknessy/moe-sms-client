<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Voter Registry" crumb="ICT/Voter Registry" description="ICT and voter registry records" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>