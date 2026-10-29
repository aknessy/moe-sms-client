<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Posting & Deployment">
            @php
                $control = 'w-full bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md px-3 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow';
                $readonlyControl = str_replace('bg-white', 'bg-base-200 cursor-not-allowed', $control);
            @endphp

            <x-registry.card title="Record a Posting"
                             description="A transfer moves an officer to another state; a deployment moves them within the one they are in">
                <form method="POST" action="{{ route('posting-deployment.store') }}">
                    @csrf

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.picker name="basic_information_ulid" label="Staff" :options="$staffOptions" required />
                        <x-registry.field name="posting_type" label="Type" required :options="$postingTypes" />
                        <x-registry.field name="effective_date" label="Effective From" type="date" required />
                    </div>

                    {{-- Which of the two sets below is shown follows the type;
                         the hidden one's fields are disabled, so nothing from it
                         is submitted. --}}
                    <div class="mb-3 w-full hidden flex-col gap-4 lg:flex-row" data-posting-fields="transfer">
                        {{-- Where they stand now, so it is read from the record
                             rather than typed. --}}
                        <div class="w-full">
                            <label class="font-sans text-sm font-medium text-base-content">From State</label>
                            <div class="relative mt-1">
                                <input type="text" readonly data-current-state
                                       placeholder="Choose a staff member first"
                                       class="{{ $readonlyControl }}" />
                            </div>
                        </div>

                        <x-registry.field name="to_state" label="To State" required :options="$states" />

                        <div class="w-full">
                            <label for="transfer_to_lga" class="font-sans text-sm font-medium text-base-content">
                                To LGA<span class="text-error">*</span>
                            </label>
                            <div class="relative mt-1">
                                {{-- Filled from the chosen state. --}}
                                <select id="transfer_to_lga" name="to_lga" required
                                        data-lga-select data-chosen="{{ old('to_lga') }}"
                                        class="{{ $control }} cursor-pointer">
                                    <option value="">Select a state first</option>
                                </select>
                            </div>
                            @error('to_lga')
                                <p class="mt-1 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-registry.field name="duty_station" label="Duty Station" required placeholder="e.g. Calabar HQ" />
                    </div>

                    <div class="mb-3 w-full hidden flex-col gap-4 lg:flex-row" data-posting-fields="deployment">
                        <div class="w-full">
                            <label class="font-sans text-sm font-medium text-base-content">Present Station</label>
                            <div class="relative mt-1">
                                <input type="text" readonly data-current-station
                                       placeholder="Choose a staff member first"
                                       class="{{ $readonlyControl }}" />
                            </div>
                        </div>

                        <div class="w-full">
                            <label for="deployment_to_lga" class="font-sans text-sm font-medium text-base-content">
                                To LGA<span class="text-error">*</span>
                            </label>
                            <div class="relative mt-1">
                                {{-- A deployment stays in the state the officer
                                     is already posted to, so only its LGAs are
                                     offered. --}}
                                <select id="deployment_to_lga" name="to_lga" required
                                        data-lga-select data-chosen="{{ old('to_lga') }}"
                                        class="{{ $control }} cursor-pointer">
                                    <option value="">Select a staff member first</option>
                                </select>
                            </div>
                            @error('to_lga')
                                <p class="mt-1 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-registry.field name="deployed_as" label="Deployed As" required :options="$deploymentRoles" />
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

            <x-registry.card title="Posting History" :count="$postings->total()">
                <x-registry.table :headings="['Staff', 'Type', 'From', 'To', 'Duty Station', 'Effective', 'Reference']"
                                  empty="No postings recorded yet.">
                    @foreach ($postings as $posting)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">
                                {{ $posting->staff?->surname }}, {{ $posting->staff?->firstname }}
                                <span class="block text-base-content/50">{{ $posting->staff?->staff_number }}</span>
                            </td>
                            <td class="px-3 py-2 capitalize">{{ $posting->posting_type }}</td>
                            <td class="px-3 py-2">{{ $posting->origin }}</td>
                            <td class="px-3 py-2">{{ $posting->destination }}</td>
                            <td class="px-3 py-2">{{ $posting->duty_station ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $posting->effective_date }}</td>
                            <td class="px-3 py-2">{{ $posting->reference_number ?: '—' }}</td>
                        </tr>
                    @endforeach
                </x-registry.table>

                <x-registry.paginator :items="$postings" />
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>

        <script>
            // What a posting asks for depends on its kind, and both kinds read
            // from where the officer currently stands.
            (function () {
                const lgasByState = @json($lgasByState);
                const context = @json($postingContext);

                const type = document.getElementById('posting_type');
                const toState = document.getElementById('to_state');
                const staff = document.querySelector('[data-staff-picker] [data-picker-value]');

                if (! type || ! staff) {
                    return;
                }

                const sets = document.querySelectorAll('[data-posting-fields]');
                const transferLga = document.getElementById('transfer_to_lga');
                const deploymentLga = document.getElementById('deployment_to_lga');

                function whereTheyStand() {
                    return context[staff.value] || { state_id: null, state: null, station: null };
                }

                function fill(select, stateId, emptyText) {
                    const lgas = lgasByState[stateId] || [];

                    // On first pass the list is empty, so fall back to what was
                    // submitted: a rejected posting comes back with its LGA.
                    const keep = select.value || select.dataset.chosen || '';

                    select.innerHTML = '';
                    select.append(new Option(lgas.length ? 'Select Option' : emptyText, ''));
                    lgas.forEach(lga => select.append(new Option(lga.name, lga.id)));

                    select.value = keep;
                }

                function sync() {
                    const standing = whereTheyStand();

                    // Readonly, and never submitted: the posting takes these
                    // from the record, not from the form.
                    document.querySelectorAll('[data-current-state]').forEach(input => {
                        input.value = standing.state || '';
                        input.placeholder = staff.value
                            ? 'No transfer on record — state unknown'
                            : 'Choose a staff member first';
                    });

                    document.querySelectorAll('[data-current-station]').forEach(input => {
                        input.value = standing.station || '';
                        input.placeholder = staff.value
                            ? 'No station on record'
                            : 'Choose a staff member first';
                    });

                    // A transfer offers the LGAs of the state it sends them to;
                    // a deployment, those of the state they are already in.
                    fill(transferLga, toState ? toState.value : '', 'Select a state first');
                    fill(deploymentLga, standing.state_id, staff.value
                        ? 'This officer has no state on record'
                        : 'Select a staff member first');

                    sets.forEach(set => {
                        const shown = set.dataset.postingFields === type.value;

                        set.classList.toggle('hidden', ! shown);
                        set.classList.toggle('flex', shown);

                        // A disabled field is not submitted, so the set that is
                        // put away cannot contribute to the posting — which
                        // matters where both sets name the same field.
                        set.querySelectorAll('input, select, textarea').forEach(field => {
                            field.disabled = ! shown;
                        });
                    });
                }

                [type, staff, toState].forEach(el => el && el.addEventListener('change', sync));
                sync();
            })();
        </script>
    </x-slot>
</x-dashboard>
