<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Allowances" crumb="Finance/Account" description="Housing, transport, hazard and responsibility allowances" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
