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

            <x-registry.card title="Promotion"
                             description="How far down the grade levels a promotion exercise reaches.">
                <form method="POST" action="{{ route('config.rules') }}">
                    @csrf

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <div class="w-full lg:max-w-xs">
                            <label for="promotion_lowest_grade_level"
                                   class="font-sans text-sm font-medium text-base-content">
                                Lowest Grade Level Considered<span class="text-error">*</span>
                            </label>
                            <div class="relative mt-1">
                                <input id="promotion_lowest_grade_level" name="promotion_lowest_grade_level"
                                       type="number" step="1" min="1" max="{{ $topGradeLevel }}" required
                                       value="{{ old('promotion_lowest_grade_level', $lowestGradeLevel) }}"
                                       class="w-full bg-white text-base-content/80 text-xs border border-base-300 rounded-md px-3 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" />
                            </div>
                            @error('promotion_lowest_grade_level')
                                <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full lg:flex-1">
                            <p class="mt-6 mb-0 text-xs text-base-content/50">
                                An officer below this grade level is left off the promotion brief.
                                GL {{ $topGradeLevel }} cannot be promoted at all, so setting this
                                to {{ $topGradeLevel }} would leave nobody to consider.
                            </p>
                        </div>
                    </div>

                    <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Save Promotion Rules</button>
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
