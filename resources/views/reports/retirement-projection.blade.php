<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Retirement Projection" crumb="Reports & Analytics" description="Staff due to exit in the next 12 to 24 months" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
