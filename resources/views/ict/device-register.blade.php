<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Device & Asset Register" crumb="ICT/Voter Registry" description="Device allocation and custody records" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
