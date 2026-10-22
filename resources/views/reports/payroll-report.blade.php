<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Payroll Report" crumb="Reports & Analytics">
            <x-registry.card title="Payroll Report"
                             description="Payroll summaries and variance reporting">
                <div class="py-10 text-center">
                    <p class="m-0 text-sm text-base-content/70">
                        Waiting on Finance/Account.
                    </p>
                    <p class="mx-auto mt-2 mb-0 max-w-xl text-sm text-base-content/60">
                        A payroll report needs a salary structure, allowances and deductions to
                        report against. None of those are held yet, so there are no figures to
                        summarise and nothing to measure a variance from.
                    </p>
                    <p class="mx-auto mt-4 mb-0 max-w-xl text-xs text-base-content/50">
                        The establishment this would be costed against is already on record —
                        see <a href="{{ route('nominal-roll-report') }}" class="text-[#2E7D32] hover:underline">Nominal Roll Report</a>
                        for the headcount by grade level and step.
                    </p>
                </div>
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
