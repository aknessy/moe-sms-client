<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Retirement & Exit" crumb="Pension & Gratuity">
            <x-registry.card title="Due to Retire" :count="$projection->count()"
                             description="Staff falling due within the next five years, by the mode of retirement recorded against each">
                <x-registry.table :headings="['Staff', 'File / Computer No.', 'Date of Birth', 'Date of Employment', 'Mode of Retirement', 'Retires On', 'Time Left']"
                                  empty="No staff fall due within the next five years.">
                    @foreach ($projection as $row)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">{{ $row->staff->surname }}, {{ $row->staff->firstname }}</td>
                            <td class="px-3 py-2">{{ $row->staff->staff_number }}</td>
                            <td class="px-3 py-2">{{ $row->date_of_birth?->toDateString() }}</td>
                            <td class="px-3 py-2">{{ $row->date_of_employment ?: '—' }}</td>
                            <td class="px-3 py-2">
                                @if ($row->mode)
                                    {{ $row->mode }}
                                @else
                                    {{-- No mode recorded, so age 60 is what applies. --}}
                                    <span class="text-base-content/40" title="No mode recorded; falls due at 60">Not set</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $row->retires_on?->toDateString() }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded-full px-2 py-0.5 text-xs {{ $row->months_left <= 12 ? 'bg-error/15 text-error' : 'bg-warning/20 text-warning-content' }}">
                                    {{ $row->time_left }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </x-registry.table>
            </x-registry.card>

            <x-registry.card title="Record an Exit"
                             description="Retirement, resignation, dismissal, death-in-service or transfer of service">
                <form method="POST" action="{{ route('retirement-exit.store') }}">
                    @csrf

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.picker name="basic_information_ulid" label="Staff" :options="$staffOptions" required />
                        <x-registry.field name="exit_type" label="Exit Type" required :options="[
                            'retirement' => 'Retirement',
                            'resignation' => 'Resignation',
                            'dismissal' => 'Dismissal',
                            'death' => 'Death in Service',
                            'transfer-of-service' => 'Transfer of Service',
                        ]" />
                        <x-registry.field name="effective_date" label="Effective Date" type="date" required />
                    </div>

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="reference_number" label="Reference Number" />
                        <x-registry.field name="gratuity_status" label="Gratuity" required :options="[
                            'pending' => 'Pending',
                            'processing' => 'Processing',
                            'paid' => 'Paid',
                        ]" />
                        <x-registry.field name="pension_status" label="Pension" required :options="[
                            'pending' => 'Pending',
                            'processing' => 'Processing',
                            'paid' => 'Paid',
                        ]" />
                    </div>

                    <div class="mb-3 w-full">
                        <x-registry.field name="remarks" label="Remarks" rows="3" />
                    </div>

                    <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Save Exit Record</button>
                        <button type="reset" class="btn btn-sm btn-cta-outline px-4">Reset</button>
                    </div>
                </form>
            </x-registry.card>

            <x-registry.card title="Exit Records" :count="$exits->total()">
                <x-registry.table :headings="['Staff', 'Type', 'Effective', 'Gratuity', 'Pension', 'Reference']"
                                  empty="No exits recorded yet.">
                    @foreach ($exits as $exit)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">
                                {{ $exit->staff?->surname }}, {{ $exit->staff?->firstname }}
                                <span class="block text-base-content/50">{{ $exit->staff?->staff_number }}</span>
                            </td>
                            <td class="px-3 py-2 capitalize">{{ str_replace('-', ' ', $exit->exit_type) }}</td>
                            <td class="px-3 py-2">{{ $exit->effective_date }}</td>
                            <td class="px-3 py-2 capitalize">{{ $exit->gratuity_status }}</td>
                            <td class="px-3 py-2 capitalize">{{ $exit->pension_status }}</td>
                            <td class="px-3 py-2">{{ $exit->reference_number ?: '—' }}</td>
                        </tr>
                    @endforeach
                </x-registry.table>

                <x-registry.paginator :items="$exits" />
            </x-registry.card>

            <x-pension.benefit-payments context="retirement"
                                        :benefits="$benefits"
                                        :staffOptions="$staffOptions"
                                        :payments="$payments"
                                        description="Gratuity and pension paid against an exit, with the receipt that evidences each payment." />
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
