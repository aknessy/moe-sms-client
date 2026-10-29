<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-section-placeholder title="CPS" crumb="Pension & Gratuity"
                               description="Contributory Pension Scheme: RSA registration, employer and employee contributions, and remittance to the PFAs" />

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
