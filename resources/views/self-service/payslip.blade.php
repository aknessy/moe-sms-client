<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        {{-- Nothing to show until the Finance/Account schema exists: there is
             no salary structure, no allowances and no deductions on record, so
             a payslip would have nothing to add up. --}}
        <x-section-placeholder title="Payslip" crumb="My Records"
                               description="Your monthly payslip, once Finance/Account is in place" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
