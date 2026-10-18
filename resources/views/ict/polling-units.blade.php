<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Polling Units" crumb="ICT/Voter Registry" description="Polling unit register and assignments" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
