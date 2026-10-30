<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Leave Management" crumb="Human Resources">
            <x-registry.card title="Record a Leave Application"
                             description="An officer applying to go on leave">
                <form method="POST" action="{{ route('hr-leave.store') }}">
                    @csrf

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.picker name="basic_information_ulid" label="Staff" :options="$staffOptions" required />
                        <x-registry.field name="leave_type" label="Type of Leave" required :options="$leaveTypes" />
                    </div>

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="effective_date" label="Effective Date" type="date" required />

                        <div class="w-full">
                            <label for="days" class="font-sans text-sm font-medium text-base-content">
                                Number of Days<span class="text-error">*</span>
                            </label>
                            <div class="relative mt-1">
                                {{-- Readonly for annual leave only: that is the
                                     one the officer's grade level sets the
                                     length of. Every other kind runs for as
                                     long as the case calls for, so it is asked
                                     for. --}}
                                <input id="days" name="days" type="number" min="1" max="365"
                                       value="{{ old('days') }}" required data-days
                                       class="w-full text-base-content/80 text-xs border border-base-300 rounded-md px-3 py-2" />
                            </div>
                            <p class="mt-1 mb-0 text-xs text-base-content/50" data-entitlement-note></p>
                            @error('days')
                                <p class="mt-1 mb-0 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full">
                            <label class="font-sans text-sm font-medium text-base-content">Resumption Date</label>
                            <div class="relative mt-1">
                                {{-- Worked out from the two fields beside it, so
                                     the three cannot contradict each other. --}}
                                <input type="text" readonly data-resumption
                                       placeholder="Set from the effective date and days"
                                       class="w-full bg-base-200 cursor-not-allowed text-base-content/80 text-xs border border-base-300 rounded-md px-3 py-2" />
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 w-full">
                        <x-registry.field name="reason" label="Reason" rows="3"
                                          placeholder="Why the leave is being applied for" />
                    </div>

                    <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Save Application</button>
                        <button type="reset" class="btn btn-sm btn-cta-outline px-4">Reset</button>
                    </div>
                </form>
            </x-registry.card>

            <x-registry.card title="Leave Applications" :count="$applications->total()"
                             description="Newest first">
                <x-registry.table :headings="['S/N', 'Staff Name', 'Grade Level', 'Effective Date', 'No. of Days', 'Resumption Date', 'Action']"
                                  empty="No leave applications yet.">
                    @foreach ($applications as $index => $application)
                        <tr class="hover:bg-base-200/60">
                            {{-- Serial continues across pages rather than restarting at 1. --}}
                            <td class="px-3 py-2 text-base-content/60">{{ $applications->firstItem() + $index }}</td>
                            <td class="px-3 py-2 font-medium text-base-content">
                                {{ trim($application->staff?->surname . ', ' . trim($application->staff?->firstname . ' ' . $application->staff?->othernames)) }}
                                <span class="block text-base-content/50">{{ $application->staff?->staff_number }}</span>
                            </td>
                            <td class="px-3 py-2">{{ $application->staff?->grade_level_label ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $application->effective_date }}</td>
                            <td class="px-3 py-2">{{ $application->days }}</td>
                            <td class="px-3 py-2">{{ $application->resumption_date }}</td>
                            <td class="px-3 py-2">
                                {{-- What this does is still to be decided. --}}
                                <button type="button" disabled
                                        class="rounded-md border border-base-300 px-2 py-1 text-xs text-base-content/40 cursor-not-allowed">
                                    Action
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </x-registry.table>

                <x-registry.paginator :items="$applications" />
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>

        <script>
            // The day count is not asked for when the leave is annual — that
            // is the one the officer's grade level sets the length of — and
            // the resumption date is never asked for. The server works both
            // out again on save; this is so the clerk can see them.
            (function () {
                const entitlements = @json($entitlements);
                const ENTITLEMENT_TYPE = @json(\App\Models\LeaveApplication::ENTITLEMENT_TYPE);

                const staff = document.querySelector('[data-staff-picker] [data-picker-value]');
                const type = document.getElementById('leave_type');
                const effective = document.getElementById('effective_date');
                const days = document.querySelector('[data-days]');
                const resumption = document.querySelector('[data-resumption]');
                const note = document.querySelector('[data-entitlement-note]');

                if (! staff || ! type || ! effective || ! days || ! resumption) {
                    return;
                }

                const readonlyClasses = ['bg-base-200', 'cursor-not-allowed'];
                const editableClasses = ['bg-white'];

                function syncDays() {
                    if (type.value !== ENTITLEMENT_TYPE) {
                        days.readOnly = false;
                        days.classList.remove(...readonlyClasses);
                        days.classList.add(...editableClasses);

                        // Clear the entitlement when moving off annual leave,
                        // so a number set for one kind is not left standing
                        // for another.
                        if (days.dataset.wasAnnual === 'yes') {
                            days.value = '';
                        }

                        note.textContent = type.value
                            ? 'How many days this leave runs for.'
                            : 'Choose the kind of leave first.';

                        return;
                    }

                    days.readOnly = true;
                    days.classList.add(...readonlyClasses);
                    days.classList.remove(...editableClasses);

                    const entitlement = entitlements[staff.value] ?? null;

                    days.value = entitlement ?? '';

                    if (! staff.value) {
                        note.textContent = 'Choose the officer, and their grade level sets this.';
                    } else if (entitlement === null) {
                        note.textContent = 'This officer has no grade level on record, so there '
                            + 'is nothing to work their annual leave entitlement out from.';
                    } else {
                        note.textContent = 'Set by the officer’s grade level. '
                            + 'GL 1–3 → 14 days, GL 4–6 → 21 days, '
                            + 'GL 7 and above → 30 days.';
                    }
                }

                function syncResumption() {
                    const count = parseInt(days.value, 10);

                    if (! effective.value || ! count || count < 1) {
                        resumption.value = '';
                        return;
                    }

                    // They resume the day after the last day of the leave.
                    const date = new Date(effective.value + 'T00:00:00');
                    date.setDate(date.getDate() + count);

                    // Read back in local time: toISOString() would shift the
                    // date by a day for anyone not on UTC, and disagree with
                    // what the server stores.
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

                staff.addEventListener('change', sync);
                type.addEventListener('change', sync);
                effective.addEventListener('input', syncResumption);
                days.addEventListener('input', syncResumption);
                sync();
            })();
        </script>
    </x-slot>
</x-dashboard>
