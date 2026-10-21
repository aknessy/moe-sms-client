<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Deceased" crumb="Pension & Gratuity">
            <x-registry.card title="Record a Death"
                             description="Marks an officer as deceased on their staff record. Choose 'No' to reverse an entry made against the wrong person.">
                <form method="POST" action="{{ route('deceased.store') }}">
                    @csrf

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.picker name="basic_information_ulid" label="Staff" :options="$staffOptions" required />
                        <x-registry.field name="is_deceased" label="Deceased" required :options="[
                            '1' => 'Yes — officer is deceased',
                            '0' => 'No — not deceased',
                        ]" />
                    </div>

                    <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Save Record</button>
                        <button type="reset" class="btn btn-sm btn-cta-outline px-4">Reset</button>
                    </div>
                </form>
            </x-registry.card>

            <x-registry.card title="Deceased Officers" :count="$deceased->total()"
                             description="Officers recorded as deceased. Death benefits and gratuity are payable to the next of kin on each record.">
                <x-registry.table :headings="['File / Computer No.', 'Name', 'Department', 'Designation', 'GL / Step', 'Date of Birth', 'Date of Death', 'Benefits', 'Action']"
                                  empty="No officer is recorded as deceased.">
                    @foreach ($deceased as $person)
                        @php $exit = $deathExits[$person->ulid] ?? null; @endphp

                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">{{ $person->staff_number }}</td>
                            <td class="px-3 py-2">{{ trim($person->surname . ', ' . trim($person->firstname . ' ' . $person->othernames)) }}</td>
                            <td class="px-3 py-2">{{ $person->department ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->designation ?: '—' }}</td>
                            <td class="px-3 py-2">{{ trim(($person->grade_level ?: '—') . ($person->step ? ' / ' . $person->step : '')) }}</td>
                            <td class="px-3 py-2">{{ $person->date_of_birth ?: '—' }}</td>
                            <td class="px-3 py-2">
                                {{-- Taken from the exit record, where a death was logged there. --}}
                                {{ $exit?->effective_date ?: '—' }}
                            </td>
                            <td class="px-3 py-2">
                                @if ($exit)
                                    <span class="block text-base-content/60">Gratuity: {{ ucfirst($exit->gratuity_status) }}</span>
                                    <span class="block text-base-content/60">Pension: {{ ucfirst($exit->pension_status) }}</span>
                                @else
                                    <span class="text-base-content/40">No exit recorded</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <form method="POST" action="{{ route('deceased.store') }}">
                                    @csrf
                                    <input type="hidden" name="basic_information_ulid" value="{{ $person->ulid }}" />
                                    <input type="hidden" name="is_deceased" value="0" />
                                    <button type="submit" class="text-xs text-[#2E7D32] hover:underline">Reverse</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </x-registry.table>

                <x-registry.paginator :items="$deceased" />
            </x-registry.card>

            <x-pension.benefit-payments context="deceased"
                                        :benefits="$benefits"
                                        :staffOptions="$deceasedOptions"
                                        :payments="$payments"
                                        description="Condolence Pause, Hazard and Group Life paid to the next of kin, with the receipt that evidences each payment."
                                        emptyPicker="No officer is recorded as deceased, so there is no death benefit to pay." />
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
