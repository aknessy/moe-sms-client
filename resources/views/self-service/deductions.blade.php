<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        {{-- As with the payslip: deductions are held against a salary
             structure, and there is not one yet. --}}
        <x-section-placeholder title="Deductions" crumb="My Records"
                               description="What is taken off your salary, once Finance/Account is in place" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
