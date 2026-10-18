<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Payroll Report" crumb="Reports & Analytics" description="Payroll summaries and variance reporting" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
