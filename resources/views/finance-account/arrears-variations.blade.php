<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Arrears & Variations" crumb="Finance/Account" description="Monthly payroll deltas before a run" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
