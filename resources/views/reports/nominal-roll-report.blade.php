<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Nominal Roll Report" crumb="Reports & Analytics" description="Nominal roll by department, state, LGA and grade level" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
