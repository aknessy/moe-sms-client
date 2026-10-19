<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Posting & Deployment">
            <x-registry.card title="Record a Posting"
                             description="Transfers, deployments and secondments between departments or duty stations">
                <form method="POST" action="{{ route('posting-deployment.store') }}">
                    @csrf

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.picker name="basic_information_ulid" label="Staff" :options="$staffOptions" required />
                        <x-registry.field name="posting_type" label="Type" required :options="[
                            'transfer' => 'Transfer',
                            'deployment' => 'Deployment',
                            'secondment' => 'Secondment',
                        ]" />
                        <x-registry.field name="effective_date" label="Effective From" type="date" required />
                    </div>

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="from_department" label="From Department" placeholder="Current department" />
                        <x-registry.field name="to_department" label="To Department" placeholder="Receiving department" required />
                        <x-registry.field name="duty_station" label="Duty Station" placeholder="e.g. Calabar HQ" />
                    </div>

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="reference_number" label="Reference Number" placeholder="Posting letter reference" />
                    </div>

                    <div class="mb-3 w-full">
                        <x-registry.field name="remarks" label="Remarks" rows="3" placeholder="Any note worth keeping on the record" />
                    </div>

                    <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Save Posting</button>
                        <button type="reset" class="btn btn-sm btn-cta-outline px-4">Reset</button>
                    </div>
                </form>
            </x-registry.card>

            <x-registry.card title="Posting History" :count="$postings->count()">
                <x-registry.table :headings="['Staff', 'Type', 'From', 'To', 'Duty Station', 'Effective', 'Reference']"
                                  empty="No postings recorded yet.">
                    @foreach ($postings as $posting)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">
                                {{ $posting->staff?->surname }}, {{ $posting->staff?->firstname }}
                                <span class="block text-base-content/50">{{ $posting->staff?->computer_number }}</span>
                            </td>
                            <td class="px-3 py-2 capitalize">{{ $posting->posting_type }}</td>
                            <td class="px-3 py-2">{{ $posting->from_department ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $posting->to_department }}</td>
                            <td class="px-3 py-2">{{ $posting->duty_station ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $posting->effective_date }}</td>
                            <td class="px-3 py-2">{{ $posting->reference_number ?: '—' }}</td>
                        </tr>
                    @endforeach
                </x-registry.table>
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
