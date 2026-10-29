<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Audit Trail" crumb="Administration" description="Who changed which staff record, and when" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
