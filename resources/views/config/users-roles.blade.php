<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Users, Roles & Permissions" crumb="Administration" description="System users and what each role may do" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
