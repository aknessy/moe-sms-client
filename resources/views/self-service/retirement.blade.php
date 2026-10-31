<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Voluntary Retirement" crumb="My Records">
            <x-registry.card title="Apply to Retire Early"
                             :description="$forOthers
                                ? 'For yourself or for another officer. The application goes to Human Resources for a decision; nothing changes on the record until it is approved.'
                                : 'Your application goes to Human Resources for a decision. Nothing changes on your record until it is approved.'">

                {{-- An officer with a request already waiting has nothing to
                     fill in. Whoever may raise one for someone else always
                     has: the form is not only about them. --}}
                @if ($pending && ! $forOthers)
                    <div class="py-6 text-center">
                        <p class="m-0 text-sm text-base-content/60">
                            You already have an application awaiting a decision.
                        </p>
                        <p class="mt-1 mb-0 text-xs text-base-content/40">
                            You may make another once this one has been decided.
                        </p>
                    </div>
                @else
                    @if (! $forOthers && $retiresOn !== null)
                        <div class="mb-3 rounded-md border border-base-300 bg-base-200 px-4 py-3 text-sm text-base-content/70">
                            You fall due to leave the service on <strong>{{ $retiresOn->toDateString() }}</strong>
                            by {{ strtolower($staff->mode_of_retirement_label ?? 'the usual route') }}.
                            This application is for leaving before then.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('my-retirement.store') }}">
                        @csrf

                        @if ($forOthers)
                            <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                <x-registry.picker name="basic_information_ulid" label="Officer"
                                                   :options="$staffOptions" required
                                                   placeholder="Search by name, file or computer number" />
                                <x-registry.field name="intended_date" label="Intended Date of Retirement"
                                                  type="date" required />
                            </div>

                            {{-- When the officer picked falls due anyway, so
                                 it is plain what they are bringing forward. --}}
                            <p class="mb-3 mt-0 text-xs text-base-content/50" data-due-note>
                                Choose the officer the application is for.
                            </p>
                        @else
                            <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                <x-registry.field name="intended_date" label="Intended Date of Retirement"
                                                  type="date" required />
                                <div class="w-full"></div>
                            </div>
                        @endif

                        <div class="mb-3 w-full">
                            <x-registry.field name="reason" label="Reason" rows="4" required
                                              :placeholder="$forOthers
                                                ? 'Why the officer is applying to retire early'
                                                : 'Why you are applying to retire early'" />
                        </div>

                        <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                            <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Submit Application</button>
                            <button type="reset" class="btn btn-sm btn-cta-outline px-4">Reset</button>
                        </div>
                    </form>
                @endif
            </x-registry.card>

            <x-registry.card title="My Applications" :count="$requests->count()"
                             description="Newest first">
                <x-registry.table :headings="['S/N', 'Date Applied', 'Intended Date', 'Reason', 'Status']"
                                  empty="You have not applied for voluntary retirement.">
                    @foreach ($requests as $index => $request)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 text-base-content/60">{{ $index + 1 }}</td>
                            <td class="px-3 py-2">{{ $request->created_at?->toDateString() }}</td>
                            <td class="px-3 py-2 font-medium text-base-content">{{ $request->intended_date }}</td>
                            <td class="px-3 py-2 text-base-content/70">{{ $request->reason }}</td>
                            <td class="px-3 py-2">
                                <x-status-pill :status="$request->status"
                                               :label="\App\Models\VoluntaryRetirementRequest::STATUSES[$request->status] ?? 'Pending'" />
                                @if (filled($request->remarks))
                                    <span class="block mt-1 text-xs text-base-content/50">{{ $request->remarks }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-registry.table>
            </x-registry.card>

            {{-- Everyone else's, for whoever has to deal with them. --}}
            @if ($others !== null)
                <x-registry.card title="Staff Applications for Voluntary Retirement" :count="$others->count()"
                                 description="Every other officer's, newest first">
                    <x-registry.table wide
                                      :headings="['S/N', 'Staff Name', 'Grade Level', 'Department', 'Date Applied', 'Intended Date', 'Reason', 'Status']"
                                      empty="No other officer has applied for voluntary retirement.">
                        @foreach ($others as $index => $request)
                            <tr class="hover:bg-base-200/60">
                                <td class="px-3 py-2 text-base-content/60">{{ $index + 1 }}</td>
                                <td class="px-3 py-2 font-medium text-base-content">
                                    {{ trim($request->staff?->surname . ', ' . trim($request->staff?->firstname . ' ' . $request->staff?->othernames)) }}
                                    <span class="block text-base-content/50">{{ $request->staff?->staff_number }}</span>
                                </td>
                                <td class="px-3 py-2">{{ $request->staff?->grade_level_label ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $request->staff?->department ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $request->created_at?->toDateString() }}</td>
                                <td class="px-3 py-2">{{ $request->intended_date }}</td>
                                <td class="px-3 py-2 text-base-content/70">{{ $request->reason }}</td>
                                <td class="px-3 py-2">
                                    <x-status-pill :status="$request->status"
                                                   :label="\App\Models\VoluntaryRetirementRequest::STATUSES[$request->status] ?? 'Pending'" />
                                </td>
                            </tr>
                        @endforeach
                    </x-registry.table>
                </x-registry.card>
            @endif
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>

        @if ($forOthers)
            <script>
                // Picking an officer says when they fall due to leave anyway,
                // so it is plain what the application brings forward.
                (function () {
                    const due = @json($dueDates);

                    const picker = document.querySelector('[data-staff-picker] [data-picker-value]');
                    const note = document.querySelector('[data-due-note]');

                    if (! picker || ! note) {
                        return;
                    }

                    const original = note.textContent;

                    function sync() {
                        const theirs = due[picker.value] ?? null;

                        if (! picker.value) {
                            note.textContent = original;
                            return;
                        }

                        note.textContent = theirs && theirs.on
                            ? 'This officer falls due to leave on ' + theirs.on
                              + (theirs.mode ? ' by ' + theirs.mode.toLowerCase() : '')
                              + '. The application is for leaving before then.'
                            : 'There is no date of birth or date of employment on this '
                              + 'officer’s record, so there is nothing to say when they '
                              + 'would otherwise leave.';
                    }

                    picker.addEventListener('change', sync);
                    sync();
                })();
            </script>
        @endif
    </x-slot>
</x-dashboard>
