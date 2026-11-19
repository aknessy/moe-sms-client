<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Request an Update" crumb="My Records">
            <x-registry.card title="Something Incorrect?"
                             description="Say what it should read instead. The Registry reviews it before it is written onto your record.">
                <form method="POST" action="{{ route('my-record-amendment.store') }}">
                    @csrf

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="field" label="Field to Correct" required
                                         :options="\App\Models\RecordAmendment::AMENDABLE_FIELDS" />
                        <x-registry.field name="current_value" label="Current Value"
                                         placeholder="As it stands on your record" />
                        <x-registry.field name="requested_value" label="Requested Value"
                                         placeholder="What it should read" required />
                    </div>

                    <div class="mb-3 w-full">
                        <x-registry.field name="reason" label="Reason for the Correction" rows="3"
                                         placeholder="Why the record is wrong, and anything that supports the change" />
                    </div>

                    <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Submit Request</button>
                        <button type="reset" class="btn btn-sm btn-cta-outline px-4">Reset</button>
                    </div>
                </form>
            </x-registry.card>

            <x-registry.card title="Your Requests" :count="$requests->count()" description="Newest first">
                <x-registry.table :headings="['Field', 'From', 'To', 'Status', 'Reviewed']"
                                  empty="You have not asked for a correction yet.">
                    @foreach ($requests as $request)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">
                                {{ \App\Models\RecordAmendment::AMENDABLE_FIELDS[$request->field] ?? str_replace('_', ' ', $request->field) }}
                            </td>
                            <td class="px-3 py-2 text-base-content/60">{{ $request->current_value ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $request->requested_value }}</td>
                            <td class="px-3 py-2">
                                @php $tone = ['approved' => 'bg-[#2E7D32]/15 text-[#1B5E20]', 'rejected' => 'bg-error/15 text-error', 'pending' => 'bg-base-300 text-base-content/70'][$request->status] ?? 'bg-base-300'; @endphp
                                <span class="rounded-full px-2 py-0.5 text-xs capitalize {{ $tone }}">{{ $request->status }}</span>
                            </td>
                            <td class="px-3 py-2 text-base-content/60">{{ $request->reviewed_at ?: '—' }}</td>
                        </tr>
                    @endforeach
                </x-registry.table>
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
