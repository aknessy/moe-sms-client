<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Voluntary Retirement" crumb="My Records">
            <x-registry.card title="Apply to Retire Early"
                             description="Your application goes to Human Resources for a decision. Nothing changes on your record until it is approved.">

                @if ($retiresOn !== null)
                    <div class="mb-3 rounded-md border border-base-300 bg-base-200 px-4 py-3 text-sm text-base-content/70">
                        You fall due to leave the service on <strong>{{ $retiresOn->toDateString() }}</strong>
                        by {{ strtolower($staff->mode_of_retirement_label ?? 'the usual route') }}.
                        This application is for leaving before then.
                    </div>
                @endif

                @if ($pending)
                    <div class="py-6 text-center">
                        <p class="m-0 text-sm text-base-content/60">
                            You already have an application awaiting a decision.
                        </p>
                        <p class="mt-1 mb-0 text-xs text-base-content/40">
                            You may make another once this one has been decided.
                        </p>
                    </div>
                @else
                    <form method="POST" action="{{ route('my-retirement.store') }}">
                        @csrf

                        <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                            <x-registry.field name="intended_date" label="Intended Date of Retirement"
                                              type="date" required />
                            <div class="w-full"></div>
                        </div>

                        <div class="mb-3 w-full">
                            <x-registry.field name="reason" label="Reason" rows="4" required
                                              placeholder="Why you are applying to retire early" />
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
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
