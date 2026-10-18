<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Recruitment" crumb="Human Resources" description="Vacancies, applicants and appointment letters" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
