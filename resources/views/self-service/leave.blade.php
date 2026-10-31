<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Leave Management" crumb="My Records">
            <x-registry.card title="Apply for Leave"
                             description="Your application goes to Human Resources for a decision">
                <form method="POST" action="{{ route('my-leave.store') }}">
                    @csrf

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="leave_type" label="Type of Leave" required :options="$leaveTypes" />
                        <x-registry.field name="effective_date" label="Effective Date" type="date" required />

                        <div class="w-full">
                            <label for="days" class="font-sans text-sm font-medium text-base-content">
                                Number of Days<span class="text-error">*</span>
                            </label>
                            <div class="relative mt-1">
                                {{-- Readonly for annual leave only: that is the
                                     one your grade level sets the length of.
                                     Every other kind runs for as long as the
                                     case calls for, so you say. --}}
                                <input id="days" name="days" type="number" min="1" max="365"
                                       value="{{ old('days') }}" required
                                       data-days data-entitlement="{{ $days }}"
                                       class="w-full text-base-content/80 text-xs border border-base-300 rounded-md px-3 py-2" />
                            </div>
                            <p class="mt-1 mb-0 text-xs text-base-content/50" data-days-note></p>
                            @error('days')
                                <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full">
                            <label class="font-sans text-sm font-medium text-base-content">Resumption Date</label>
                            <div class="relative mt-1">
                                {{-- Worked out from the two fields beside it. --}}
                                <input type="text" readonly data-resumption
                                       placeholder="Set from the effective date"
                                       class="w-full bg-base-200 cursor-not-allowed text-base-content/80 text-xs border border-base-300 rounded-md px-3 py-2" />
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 w-full">
                        <x-registry.field name="reason" label="Reason" rows="3"
                                          placeholder="Why you are applying for this leave" />
                    </div>

                    <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Submit Application</button>
                        <button type="reset" class="btn btn-sm btn-cta-outline px-4">Reset</button>
                    </div>
                </form>
            </x-registry.card>

            <x-registry.card title="My Leave Applications" :count="$applications->total()"
                             description="Newest first">
                <x-registry.table wide
                                  :headings="['S/N', 'Type', 'Effective Date', 'No. of Days', 'Resumption Date', 'Status', '']"
                                  empty="You have not applied for any leave yet.">
                    @foreach ($applications as $index => $application)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 text-base-content/60">{{ $applications->firstItem() + $index }}</td>
                            <td class="px-3 py-2 font-medium text-base-content">{{ $application->leave_type }}</td>
                            <td class="px-3 py-2">{{ $application->effective_date }}</td>
                            <td class="px-3 py-2">{{ $application->days }}</td>
                            <td class="px-3 py-2">{{ $application->resumption_date }}</td>
                            <td class="px-3 py-2">
                                <x-status-pill :status="$application->status"
                                               :label="\App\Models\LeaveApplication::STATUSES[$application->status] ?? 'Pending'" />
                                @if (filled($application->remarks))
                                    <span class="block mt-1 text-xs text-base-content/50">{{ $application->remarks }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <a href="{{ route('my-leave.show', $application->ulid) }}"
                                   class="rounded-md border border-base-300 px-2 py-1 text-xs text-base-content/70 hover:border-primary hover:text-primary">
                                    View Letter
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </x-registry.table>

                <x-registry.paginator :items="$applications" />
            </x-registry.card>

            {{-- An administrator is an officer too: the table above is their
                 own leave, this one is everyone else's. --}}
            @if ($others !== null)
                <x-registry.card title="Other Staff's Leave Applications" :count="$others->total()"
                                 description="Every other officer's, newest first">
                    {{-- Nine columns: wide, so it scrolls rather than wraps. --}}
                    <x-registry.table wide
                                      :headings="['S/N', 'Staff Name', 'Grade Level', 'Type', 'Effective Date', 'No. of Days', 'Resumption Date', 'Status', '']"
                                      empty="No other officer has applied for leave.">
                        @foreach ($others as $index => $application)
                            <tr class="hover:bg-base-200/60">
                                <td class="px-3 py-2 text-base-content/60">{{ $others->firstItem() + $index }}</td>
                                <td class="px-3 py-2 font-medium text-base-content">
                                    {{ trim($application->staff?->surname . ', ' . trim($application->staff?->firstname . ' ' . $application->staff?->othernames)) }}
                                    <span class="block text-base-content/50">{{ $application->staff?->staff_number }}</span>
                                </td>
                                <td class="px-3 py-2">{{ $application->staff?->grade_level_label ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $application->leave_type }}</td>
                                <td class="px-3 py-2">{{ $application->effective_date }}</td>
                                <td class="px-3 py-2">{{ $application->days }}</td>
                                <td class="px-3 py-2">{{ $application->resumption_date }}</td>
                                <td class="px-3 py-2">
                                    <x-status-pill :status="$application->status"
                                                   :label="\App\Models\LeaveApplication::STATUSES[$application->status] ?? 'Pending'" />
                                </td>
                                <td class="px-3 py-2">
                                    <a href="{{ route('hr-leave.show', $application->ulid) }}"
                                       class="rounded-md border border-base-300 px-2 py-1 text-xs text-base-content/70 hover:border-primary hover:text-primary">
                                        View Letter
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </x-registry.table>

                    <x-registry.paginator :items="$others" />
                </x-registry.card>
            @endif
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>

        <script>
            // Two things follow from what is already on the form rather than
            // being asked for: the day count, but only for annual leave, and
            // the resumption date. The server works both out again on save;
            // this is so you can see them before you submit.
            (function () {
                const ENTITLEMENT_TYPE = @json(\App\Models\LeaveApplication::ENTITLEMENT_TYPE);

                const type = document.getElementById('leave_type');
                const effective = document.getElementById('effective_date');
                const days = document.querySelector('[data-days]');
                const note = document.querySelector('[data-days-note]');
                const resumption = document.querySelector('[data-resumption]');

                if (! type || ! effective || ! days || ! resumption) {
                    return;
                }

                // Blank when there is no grade level on the record.
                const entitlement = parseInt(days.dataset.entitlement, 10);
                const hasEntitlement = ! Number.isNaN(entitlement);

                const readonlyClasses = ['bg-base-200', 'cursor-not-allowed'];
                const editableClasses = ['bg-white'];

                function syncDays() {
                    if (type.value === ENTITLEMENT_TYPE) {
                        // Your grade level sets how long annual leave runs for,
                        // so it is shown rather than asked for.
                        days.readOnly = true;
                        days.classList.add(...readonlyClasses);
                        days.classList.remove(...editableClasses);
                        days.value = hasEntitlement ? entitlement : '';

                        note.textContent = hasEntitlement
                            ? 'Set by your grade level ({{ $staff->grade_level_label }}).'
                            : 'There is no grade level on your record, so your annual leave '
                              + 'entitlement cannot be worked out. Apply for another kind of '
                              + 'leave, or ask the Registry to correct it.';

                        return;
                    }

                    days.readOnly = false;
                    days.classList.remove(...readonlyClasses);
                    days.classList.add(...editableClasses);

                    // Clear the entitlement when moving off annual leave, so a
                    // number set for one kind is not left standing for another.
                    if (days.dataset.wasAnnual === 'yes') {
                        days.value = '';
                    }

                    note.textContent = type.value
                        ? 'How many days this leave runs for.'
                        : 'Choose the kind of leave first.';
                }

                function syncResumption() {
                    const count = parseInt(days.value, 10);

                    if (! effective.value || ! count || count < 1) {
                        resumption.value = '';
                        return;
                    }

                    // Back at your desk the day after the last day of leave.
                    const date = new Date(effective.value + 'T00:00:00');
                    date.setDate(date.getDate() + count);

                    // Read back in local time: toISOString() would shift the
                    // date by a day for anyone not on UTC.
                    const pad = n => String(n).padStart(2, '0');

                    resumption.value = date.getFullYear()
                        + '-' + pad(date.getMonth() + 1)
                        + '-' + pad(date.getDate());
                }

                function sync() {
                    syncDays();
                    days.dataset.wasAnnual = type.value === ENTITLEMENT_TYPE ? 'yes' : 'no';
                    syncResumption();
                }

                type.addEventListener('change', sync);
                effective.addEventListener('input', syncResumption);
                days.addEventListener('input', syncResumption);
                sync();
            })();
        </script>
    </x-slot>
</x-dashboard>
