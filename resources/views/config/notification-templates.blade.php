<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Notification Templates" crumb="Administration" description="Email and SMS templates used by the system" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
