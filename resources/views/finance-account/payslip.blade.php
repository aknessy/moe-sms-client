<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Pay Slip" crumb="Finance/Account" description="Individual staff pay slips" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>