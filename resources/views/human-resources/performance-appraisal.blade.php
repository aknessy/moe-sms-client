<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Performance Appraisal" crumb="Human Resources" description="Annual Performance Evaluation Reports (APER)" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
