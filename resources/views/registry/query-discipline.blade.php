<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Query & Discipline">
            <x-registry.card title="Issue a Query"
                             description="Queries issued, the staff response and any sanction arising">
                <form method="POST" action="{{ route('query-discipline.store') }}">
                    @csrf

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.picker name="basic_information_ulid" label="Staff" :options="$staffOptions" required />
                        <x-registry.field name="query_date" label="Date Issued" type="date" required />
                        <x-registry.field name="reference_number" label="Reference Number" placeholder="Query reference" />
                    </div>

                    <div class="mb-3 w-full">
                        <x-registry.field name="offence" label="Offence" placeholder="e.g. Absence from duty without leave" required />
                    </div>

                    <div class="mb-3 w-full">
                        <x-registry.field name="details" label="Details" rows="3" placeholder="Particulars of the allegation" />
                    </div>

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="response_date" label="Date of Response" type="date" />
                        <x-registry.field name="sanction" label="Sanction" :options="[
                            'none' => 'None',
                            'caution' => 'Caution',
                            'warning' => 'Warning',
                            'suspension' => 'Suspension',
                            'demotion' => 'Demotion',
                            'dismissal' => 'Dismissal',
                        ]" />
                        <x-registry.field name="status" label="Status" required :options="[
                            'issued' => 'Issued',
                            'responded' => 'Responded',
                            'closed' => 'Closed',
                        ]" />
                    </div>

                    <div class="mb-3 w-full">
                        <x-registry.field name="response" label="Staff Response" rows="3" />
                    </div>

                    <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Save Query</button>
                        <button type="reset" class="btn btn-sm btn-cta-outline px-4">Reset</button>
                    </div>
                </form>
            </x-registry.card>

            <x-registry.card title="Disciplinary Record" :count="$queries->total()">
                <x-registry.table :headings="['Staff', 'Date', 'Offence', 'Responded', 'Sanction', 'Status', 'Reference']"
                                  empty="No queries on record.">
                    @foreach ($queries as $query)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">
                                {{ $query->staff?->surname }}, {{ $query->staff?->firstname }}
                                <span class="block text-base-content/50">{{ $query->staff?->staff_number }}</span>
                            </td>
                            <td class="px-3 py-2">{{ $query->query_date }}</td>
                            <td class="px-3 py-2">{{ $query->offence }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('query-response', $query->ulid) }}"
                                   class="text-[#2E7D32] hover:underline"
                                   title="Open the officer's response">
                                    {{ $query->response_date ?: 'Awaiting response' }}
                                </a>
                            </td>
                            <td class="px-3 py-2 capitalize">{{ $query->sanction ?: '—' }}</td>
                            <td class="px-3 py-2">
                                @php $tone = ['closed' => 'bg-[#2E7D32]/15 text-[#1B5E20]', 'responded' => 'bg-base-300 text-base-content/70', 'issued' => 'bg-error/15 text-error'][$query->status] ?? 'bg-base-300'; @endphp
                                <span class="rounded-full px-2 py-0.5 text-xs capitalize {{ $tone }}">{{ $query->status }}</span>
                            </td>
                            <td class="px-3 py-2">{{ $query->reference_number ?: '—' }}</td>
                        </tr>
                    @endforeach
                </x-registry.table>

                <x-registry.paginator :items="$queries" />
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
