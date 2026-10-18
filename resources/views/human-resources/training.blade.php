<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Training & Capacity Building" crumb="Human Resources" description="Courses, sponsorship and bonding records" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
