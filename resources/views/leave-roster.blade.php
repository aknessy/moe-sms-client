<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Leave Roster">
            <x-registry.card title="Add to the Roster"
                             description="Planned leave for the officers of {{ $department }}, or posted to it. Leave days default to the officer's entitlement for Annual Leave.">
                <form method="POST" action="{{ route('leave-roster.store') }}" class="flex w-full flex-col gap-4 py-3">
                    @csrf
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <x-registry.picker name="basic_information_ulid" label="Staff" :options="$staffOptions"
                                           :value="old('basic_information_ulid')" required />
                        <x-registry.field name="leave_type" label="Kind of Leave" :options="$leaveTypes"
                                          :value="old('leave_type', 'Annual Leave')" required />
                        <x-registry.field name="start_date" label="Starts" type="date" required />
                        <x-registry.field name="days" label="Days" type="number"
                                          placeholder="Blank for the officer's annual entitlement" />
                    </div>
                    <x-registry.field name="remarks" label="Remarks" placeholder="Optional" />
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Add to Roster</button>
                    </div>
                </form>
            </x-registry.card>

            <x-registry.card title="{{ $year }} Roster" description="{{ $viewing }}">
                {{-- The department and year sit where a card's record count
                     usually does; the count itself is at the foot of the card. --}}
                <x-slot name="action">
                    <form method="GET" action="{{ route('leave-roster') }}" class="flex items-center gap-2">
                        @if ($departments->count() > 1)
                            <label for="roster-department" class="sr-only">Department</label>
                            <select id="roster-department" name="department" onchange="this.form.submit()"
                                    class="max-w-[16rem] cursor-pointer rounded-md border border-base-300 bg-white px-3 py-1 text-xs text-base-content/80 focus:border-primary focus:outline-none">
                                @foreach ($departments as $option)
                                    <option value="{{ $option }}" @selected($option === $viewing)>{{ $option }}</option>
                                @endforeach
                            </select>
                        @endif
                        <label for="roster-year" class="sr-only">Year</label>
                        <select id="roster-year" name="year" onchange="this.form.submit()"
                                class="cursor-pointer rounded-md border border-base-300 bg-white px-3 py-1 text-xs text-base-content/80 focus:border-primary focus:outline-none">
                            @foreach ($years as $option)
                                <option value="{{ $option }}" @selected($option === $year)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </form>
                </x-slot>

                @unless ($viewingOwn)
                    <p class="mb-3 mt-0 rounded-md bg-base-200 px-3 py-2 text-xs text-base-content/70">
                        You are reading another department's roster. It can be read here, not changed.
                    </p>
                @endunless

                @error('department')
                    <p class="mb-3 mt-0 text-xs text-error">{{ $message }}</p>
                @enderror

                @if ($entries->isEmpty())
                    <x-registry.empty-state title="Nobody is on the {{ $year }} roster yet"
                                            description="{{ $viewingOwn ? 'Add an officer above to plan their leave for the year, or pick another year.' : 'Nothing has been rostered for this department in ' . $year . '. Pick another year or department.' }}" />

                    {{-- This department has not made its roster: the desk that
                         reads them all can ask its head to. --}}
                    @if ($canRemind)
                        <form method="POST" action="{{ route('leave-roster.remind') }}" class="mt-3 flex justify-center">
                            @csrf
                            <input type="hidden" name="department" value="{{ $viewing }}" />
                            <input type="hidden" name="year" value="{{ $year }}" />
                            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
                                Remind the head or delegate to create their roster
                            </button>
                        </form>
                    @endif
                @else
                    <div class="overflow-x-auto">
                        <table class="table w-full text-xs">
                            <thead>
                                <tr>
                                    <th>Staff</th>
                                    <th>Kind of Leave</th>
                                    <th>Starts</th>
                                    <th>Days</th>
                                    <th>Resumes</th>
                                    <th>Remarks</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($entries as $entry)
                                    <tr>
                                        <td>
                                            {{ $entry->staff?->surname }}, {{ $entry->staff?->firstname }}
                                            <span class="text-base-content/50">({{ $entry->staff?->staff_number }})</span>
                                        </td>
                                        <td>{{ $entry->leave_type }}</td>
                                        <td>{{ $entry->start_date->format('d M Y') }}</td>
                                        <td>{{ $entry->days }}</td>
                                        <td>{{ $entry->resumption_date->format('d M Y') }}</td>
                                        <td>{{ $entry->remarks }}</td>
                                        <td class="text-right">
                                            @if ($viewingOwn)
                                                <form method="POST" action="{{ route('leave-roster.remove', $entry->ulid) }}">
                                                    @csrf
                                                    <button type="submit" class="text-xs text-error hover:underline">Remove</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="mt-3 mb-0 text-left text-xs text-base-content/60">
                        {{ $entries->count() }} record{{ $entries->count() == 1 ? '' : 's' }}
                    </p>
                @endif
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
