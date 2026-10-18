<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Loans & Advances" crumb="Finance/Account" description="Advances and their repayment schedules" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
