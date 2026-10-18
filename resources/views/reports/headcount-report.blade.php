<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Headcount & Audit" crumb="Reports & Analytics" description="Headcount, verification and audit reporting" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
