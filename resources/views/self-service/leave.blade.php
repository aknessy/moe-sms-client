<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Leave Management" crumb="My Records">
            <x-registry.card title="Apply for Leave"
                             description="Your application goes to Human Resources for a decision">
                @if ($days === null)
                    <div class="py-6 text-center">
                        <p class="m-0 text-sm text-base-content/60">
                            There is no grade level on your record, so your leave entitlement
                            cannot be worked out.
                        </p>
                        <p class="mt-1 mb-0 text-xs text-base-content/40">
                            Ask the Registry to correct your record, then come back.
                        </p>
                    </div>
                @else
                    <form method="POST" action="{{ route('my-leave.store') }}">
                        @csrf

                        <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                            <x-registry.field name="leave_type" label="Type of Leave" required :options="$leaveTypes" />
                            <x-registry.field name="effective_date" label="Effective Date" type="date" required />

                            <div class="w-full">
                                <label class="font-sans text-sm font-medium text-base-content">Number of Days</label>
                                <div class="relative mt-1">
                                    {{-- Your entitlement, which follows from your
                                         grade level. Taken from your record on
                                         save, not from this field. --}}
                                    <input type="text" readonly data-days value="{{ $days }}"
                                           class="w-full bg-base-200 cursor-not-allowed text-base-content/80 text-xs border border-base-300 rounded-md px-3 py-2" />
                                </div>
                                <p class="mt-1 mb-0 text-xs text-base-content/50">
                                    Set by your grade level ({{ $staff->grade_level_label }}).
                                </p>
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
                @endif
            </x-registry.card>

            <x-registry.card title="My Leave Applications" :count="$applications->total()"
                             description="Newest first">
                <x-registry.table :headings="['S/N', 'Type', 'Effective Date', 'No. of Days', 'Resumption Date', 'Status']"
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
                        </tr>
                    @endforeach
                </x-registry.table>

                <x-registry.paginator :items="$applications" />
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>

        <script>
            // The resumption date follows the effective date and the day count,
            // so it is shown rather than asked for. The server works it out
            // again on save; this is only so you can see it.
            (function () {
                const effective = document.getElementById('effective_date');
                const days = document.querySelector('[data-days]');
                const resumption = document.querySelector('[data-resumption]');

                if (! effective || ! days || ! resumption) {
                    return;
                }

                function sync() {
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

                effective.addEventListener('input', sync);
                sync();
            })();
        </script>
    </x-slot>
</x-dashboard>
