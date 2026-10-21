<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Record of Service">
            <x-registry.card title="Select Staff"
                             description="A consolidated service history, assembled from every Registry record held for the officer">
                <form method="GET" action="{{ route('record-of-service') }}"
                      class="flex flex-col gap-4 lg:flex-row lg:items-end">
                    <x-registry.picker name="staff" label="Staff" :options="$staffOptions" :value="$selected" required />
                    <div class="flex flex-wrap items-center gap-2 pb-0.5">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">View Record</button>
                        @if ($record)
                            <button type="button" onclick="window.print()" class="btn btn-sm btn-cta-outline px-4">Print</button>
                        @endif
                    </div>
                </form>
            </x-registry.card>

            @if ($record)
                <x-registry.card title="{{ $record->surname }}, {{ $record->firstname }} {{ $record->othernames }}"
                                 description="{{ $record->staff_number }}">
                    <dl class="grid grid-cols-1 gap-x-8 gap-y-3 text-xs sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            'Department' => $record->department,
                            'Division / Unit' => $record->division,
                            'Designation' => $record->designation,
                            'Grade Level' => $record->grade_level,
                            'Step' => $record->step,
                            'Date of Birth' => $record->date_of_birth,
                            'Phone Number' => $record->phone_number,
                            'Email' => $record->email,
                            'State of Origin' => $record->state?->name,
                            'LGA of Origin' => $record->lga?->lga_name,
                            'Pension Fund Administrator' => $record->pfa,
                            'RSA Number' => $record->rsa,
                            'NHIS Number' => $record->nhis,
                            'Permanent Address' => $record->address,
                        ] as $label => $value)
                            <div>
                                <dt class="text-base-content/60">{{ $label }}</dt>
                                <dd class="m-0 font-medium text-base-content">{{ $value ?: '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-registry.card>

                <x-registry.card title="Postings" :count="$postings->count()">
                    <x-registry.table :headings="['Effective', 'Type', 'From', 'To', 'Duty Station']"
                                      empty="No postings on record.">
                        @foreach ($postings as $posting)
                            <tr class="hover:bg-base-200/60">
                                <td class="px-3 py-2">{{ $posting->effective_date }}</td>
                                <td class="px-3 py-2 capitalize">{{ $posting->posting_type }}</td>
                                <td class="px-3 py-2">{{ $posting->from_department ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $posting->to_department }}</td>
                                <td class="px-3 py-2">{{ $posting->duty_station ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </x-registry.table>
                </x-registry.card>

                <x-registry.card title="Queries & Discipline" :count="$queries->count()">
                    <x-registry.table :headings="['Date', 'Offence', 'Sanction', 'Status']"
                                      empty="No disciplinary record.">
                        @foreach ($queries as $query)
                            <tr class="hover:bg-base-200/60">
                                <td class="px-3 py-2">{{ $query->query_date }}</td>
                                <td class="px-3 py-2">{{ $query->offence }}</td>
                                <td class="px-3 py-2 capitalize">{{ $query->sanction ?: '—' }}</td>
                                <td class="px-3 py-2 capitalize">{{ $query->status }}</td>
                            </tr>
                        @endforeach
                    </x-registry.table>
                </x-registry.card>

                <x-registry.card title="Exit">
                    @if ($exit)
                        <dl class="grid grid-cols-1 gap-x-8 gap-y-3 text-xs sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <dt class="text-base-content/60">Exit Type</dt>
                                <dd class="m-0 font-medium capitalize text-base-content">{{ str_replace('-', ' ', $exit->exit_type) }}</dd>
                            </div>
                            <div>
                                <dt class="text-base-content/60">Effective Date</dt>
                                <dd class="m-0 font-medium text-base-content">{{ $exit->effective_date }}</dd>
                            </div>
                            <div>
                                <dt class="text-base-content/60">Gratuity</dt>
                                <dd class="m-0 font-medium capitalize text-base-content">{{ $exit->gratuity_status }}</dd>
                            </div>
                            <div>
                                <dt class="text-base-content/60">Pension</dt>
                                <dd class="m-0 font-medium capitalize text-base-content">{{ $exit->pension_status }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="py-6 text-center text-sm text-base-content/60 m-0">Officer is still in service.</p>
                    @endif
                </x-registry.card>
            @elseif ($selected)
                <x-registry.card title="Not Found">
                    <p class="py-6 text-center text-sm text-base-content/60 m-0">No staff record matches that selection.</p>
                </x-registry.card>
            @endif
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
