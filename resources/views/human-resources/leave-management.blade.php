<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="Leave Management" crumb="Human Resources" description="Leave requests, approvals, balances and roster" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
