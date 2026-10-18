<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Salary Structure" crumb="Finance/Account" description="Salary tables, grade levels and steps" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
