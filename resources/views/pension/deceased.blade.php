<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
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
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <form method="GET" action="{{ route('deceased') }}" class="flex flex-wrap items-center gap-2">
                        <label for="year" class="text-xs text-base-content/60">Year of death</label>
                        <select id="year" name="year" onchange="this.form.submit()"
                                class="min-w-[9rem] cursor-pointer rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 focus:border-primary focus:outline-none hover:border-primary/50">
                            <option value="">All years</option>
                            @foreach ($years as $option)
                                <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
                            @endforeach
                        </select>

                        @if ($year)
                            <a href="{{ route('deceased') }}" class="text-xs text-base-content/60 hover:text-[#2E7D32]">Clear</a>
                        @endif

                        @if ($years->isEmpty())
                            <span class="text-xs text-base-content/50">No date of death on file to filter by.</span>
                        @endif
                    </form>

                    {{-- Hands back the whole filtered list as a spreadsheet. --}}
                    <a href="{{ route('deceased.export', array_filter(['year' => $year])) }}"
                       class="btn btn-sm btn-cta-outline inline-flex items-center gap-2 px-4">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-4 w-4">
                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                            <path d="M7 11l5 5l5 -5"></path>
                            <path d="M12 4l0 12"></path>
                        </svg>
                        Download List
                    </a>
                </div>

                <x-registry.table :headings="['File / Computer No.', 'Name', 'Department', 'Designation', 'GL / Step', 'Date of Birth', 'Date of Death', 'Benefits', 'Action']"
                                  :empty="$year ? 'No officer is recorded as having died in ' . $year . '.' : 'No officer is recorded as deceased.'">
                    @foreach ($deceased as $person)
                        @php $exit = $deathExits[$person->ulid] ?? null; @endphp

                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">{{ $person->staff_number }}</td>
                            <td class="px-3 py-2">{{ trim($person->surname . ', ' . trim($person->firstname . ' ' . $person->othernames)) }}</td>
                            <td class="px-3 py-2">{{ $person->department ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->designation ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->grade_step }}</td>
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

            <div>
                <x-pension.benefit-payments context="deceased"
                                            :benefits="$benefits"
                                            :staffOptions="$deceasedOptions"
                                            :payments="$payments"
                                            description="Condolence Pause, Hazard and Group Life paid to the next of kin, with the receipt that evidences each payment."
                                            emptyPicker="No officer is recorded as deceased, so there is no death benefit to pay." />
            </div>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
