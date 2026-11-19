<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Family & Referees">
            <x-registry.card title="Select Staff"
                             description="Next of kin and referees for a staff record already on file — correctable here even after the Add Staff wizard is done">
                <form method="GET" action="{{ route('family-referees') }}"
                      class="flex flex-col gap-4 lg:flex-row lg:items-end">
                    <x-registry.picker name="staff" label="Staff" :options="$staffOptions" :value="$selected" required />
                    <div class="flex flex-wrap items-center gap-2 pb-0.5">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Open Record</button>
                    </div>
                </form>
            </x-registry.card>

            @if ($staff)
                @php
                    $control = 'w-full bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md px-3 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow';
                    $refereeRows = $refereeSheet?->referees_record ?? [];
                @endphp

                <x-registry.card title="Next of Kin" description="{{ $staff->surname }}, {{ $staff->firstname }} {{ $staff->othernames }}"
                                 :count="$nextOfKins->count()">
                    @forelse ($nextOfKins as $kin)
                        <div class="mb-4 rounded-md border border-base-300 p-3">
                            <form method="POST" action="{{ route('family-referees.next-of-kin.update', $kin->id) }}"
                                  class="flex flex-col gap-3 lg:flex-row lg:items-end">
                                @csrf
                                <x-registry.field name="nok_name" label="Name" :value="$kin->nok_name" required />
                                <x-registry.field name="nok_relationship" label="Relationship" :value="$kin->nok_relationship" required :options="[
                                    'Brother' => 'Brother', 'Sister' => 'Sister', 'Daughter' => 'Daughter',
                                    'Son' => 'Son', 'Wife' => 'Wife', 'Husband' => 'Husband',
                                ]" />
                                <x-registry.field name="nok_address" label="Address" :value="$kin->nok_address" required />
                                <x-registry.field name="nok_phone_number" label="Phone" :value="$kin->nok_phone_number" required />
                                <button type="submit" class="btn btn-sm btn-cta-outline px-4">Save</button>
                            </form>
                            <form method="POST" action="{{ route('family-referees.next-of-kin.remove', $kin->id) }}" class="mt-2 text-right">
                                @csrf
                                <button type="submit" class="text-xs text-error hover:underline">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-base-content/60 m-0">No next of kin on record yet.</p>
                    @endforelse

                    <div class="mt-2 border-t border-base-200 pt-4">
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-base-content/40">Add Next of Kin</p>
                        <form method="POST" action="{{ route('family-referees.next-of-kin.store') }}"
                              class="flex flex-col gap-3 lg:flex-row lg:items-end">
                            @csrf
                            <input type="hidden" name="basic_information_ulid" value="{{ $staff->ulid }}">
                            <x-registry.field name="nok_name" label="Name" required />
                            <x-registry.field name="nok_relationship" label="Relationship" required :options="[
                                'Brother' => 'Brother', 'Sister' => 'Sister', 'Daughter' => 'Daughter',
                                'Son' => 'Son', 'Wife' => 'Wife', 'Husband' => 'Husband',
                            ]" />
                            <x-registry.field name="nok_address" label="Address" required />
                            <x-registry.field name="nok_phone_number" label="Phone" required />
                            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Add</button>
                        </form>
                    </div>
                </x-registry.card>

                <x-registry.card title="Referees" description="Saving replaces the whole list">
                    <form method="POST" action="{{ route('family-referees.referees.store') }}" class="flex flex-col gap-4">
                        @csrf
                        <input type="hidden" name="basic_information_ulid" value="{{ $staff->ulid }}">

                        @for ($i = 0; $i < max(count($refereeRows), 1) + 2; $i++)
                            <div class="flex flex-col gap-3 rounded-md border border-base-300 p-3 lg:flex-row">
                                <div class="w-full">
                                    <label for="referee-name-{{ $i }}" class="font-sans text-sm font-medium text-base-content">Name</label>
                                    <input id="referee-name-{{ $i }}" name="referee-name[]" type="text"
                                           value="{{ old("referee-name.$i", $refereeRows[$i]['name'] ?? '') }}"
                                           placeholder="Name of Referee" class="mt-1 {{ $control }}" />
                                </div>
                                <div class="w-full">
                                    <label for="referee-address-{{ $i }}" class="font-sans text-sm font-medium text-base-content">Address</label>
                                    <input id="referee-address-{{ $i }}" name="referee-address[]" type="text"
                                           value="{{ old("referee-address.$i", $refereeRows[$i]['address'] ?? '') }}"
                                           placeholder="Address of Referee" class="mt-1 {{ $control }}" />
                                </div>
                                <div class="w-full">
                                    <label for="referee-phone-{{ $i }}" class="font-sans text-sm font-medium text-base-content">Phone</label>
                                    <input id="referee-phone-{{ $i }}" name="referee-phone[]" type="text"
                                           value="{{ old("referee-phone.$i", $refereeRows[$i]['phone'] ?? '') }}"
                                           placeholder="Referee Phone" class="mt-1 {{ $control }}" />
                                </div>
                                <div class="w-full">
                                    <label for="referee-email-{{ $i }}" class="font-sans text-sm font-medium text-base-content">Email</label>
                                    <input id="referee-email-{{ $i }}" name="referee-email[]" type="email"
                                           value="{{ old("referee-email.$i", $refereeRows[$i]['email'] ?? '') }}"
                                           placeholder="Referee Email" class="mt-1 {{ $control }}" />
                                </div>
                            </div>
                        @endfor

                        <p class="m-0 text-xs text-base-content/50">A row left entirely blank is dropped rather than saved.</p>

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Save Referees</button>
                        </div>
                    </form>
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
