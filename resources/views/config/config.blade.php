<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Settings" crumb="Administration">

            <x-registry.card title="Organisation Details"
                             description="These head every letter the system raises — a leave application, and whatever follows it.">
                <form method="POST" action="{{ route('config.organisation') }}">
                    @csrf

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="organisation_name" label="Name" required
                                          :value="$organisation['organisation.name']"
                                          placeholder="Independent National Electoral Commission" />
                        <x-registry.field name="organisation_command" label="Command / Office"
                                          :value="$organisation['organisation.command']"
                                          placeholder="e.g. Cross River State Office" />
                    </div>

                    <div class="mb-3 w-full">
                        <x-registry.field name="organisation_address" label="Address" rows="3"
                                          :value="$organisation['organisation.address']"
                                          placeholder="The postal address, as it should print on a letterhead" />
                    </div>

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="organisation_telephone" label="Telephone"
                                          :value="$organisation['organisation.telephone']"
                                          placeholder="e.g. 08030000000" />
                        <x-registry.field name="organisation_email" label="Email" type="email"
                                          :value="$organisation['organisation.email']"
                                          placeholder="e.g. crossriver@inecnigeria.org" />
                    </div>

                    <div class="mb-3 w-full">
                        <x-registry.field name="organisation_commissioner_title"
                                          label="Applications are addressed to" required
                                          :value="$organisation['organisation.commissioner_title']"
                                          placeholder="The Resident Electoral Commissioner" />
                        <p class="mt-1 mb-0 text-xs text-base-content/50">
                            The desk a staff application is finally addressed to, after the
                            Administrative Secretary and the officer's own head of department.
                        </p>
                    </div>

                    <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Save Details</button>
                    </div>
                </form>
            </x-registry.card>

            <x-registry.card title="Heads of Department" :count="$departmentHeads->count()"
                             description="Who an application passes on its way up. A department with nobody named simply goes to the administrators.">

                {{-- The staff wizard offers these and nothing else, so a new
                     directorate has to be recorded here before anyone can be
                     put in it. --}}
                <form method="POST" action="{{ route('config.departments') }}"
                      class="mb-4 flex flex-col gap-3 rounded-md border border-base-300 bg-base-200/40 px-4 py-3 sm:flex-row sm:items-end">
                    @csrf

                    <x-registry.field name="department" label="Add a Department"
                                      placeholder="e.g. Department of Legal Services" />

                    <div class="w-full sm:max-w-xs">
                        <label for="role" class="font-sans text-sm font-medium text-base-content">Headed By</label>
                        <div class="relative mt-1">
                            <select id="role" name="role"
                                    class="w-full cursor-pointer rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 focus:outline-none focus:border-primary">
                                <option value="">Nobody named</option>
                                @foreach ($hodRoles as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Add</button>
                </form>

                <form method="POST" action="{{ route('config.department-heads') }}">
                    @csrf

                    <x-registry.table :headings="['Department', 'Headed By']"
                                      empty="No departments on any staff record yet.">
                        @foreach ($departmentHeads as $head)
                            <tr class="hover:bg-base-200/60">
                                <td class="px-3 py-2 font-medium text-base-content">{{ $head->department }}</td>
                                <td class="px-3 py-2">
                                    <select name="heads[{{ $head->id }}]"
                                            class="w-full max-w-xs cursor-pointer rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 focus:outline-none focus:border-primary">
                                        <option value="">Nobody named</option>
                                        @foreach ($hodRoles as $value => $label)
                                            <option value="{{ $value }}" @selected($head->role === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </x-registry.table>

                    <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Save Heads</button>
                    </div>
                </form>
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
