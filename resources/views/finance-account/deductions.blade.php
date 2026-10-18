<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Staff Deductions" crumb="Finance/Account" description="Deductions applied against staff salaries" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>