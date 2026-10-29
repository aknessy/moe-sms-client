<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Remittances" crumb="Finance/Account" description="PAYE, NHF and pension remittances" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
