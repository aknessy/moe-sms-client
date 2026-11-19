<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Record Amendments">
            <x-registry.card title="Request an Amendment"
                             description="Corrections to a staff record. Approving a request writes the new value onto the bio-data.">
                <form method="POST" action="{{ route('record-amendments.store') }}">
                    @csrf

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.picker name="basic_information_ulid" label="Staff" :options="$staffOptions" required />
                        <x-registry.field name="field" label="Field to Amend" required
                                         :options="\App\Models\RecordAmendment::AMENDABLE_FIELDS" />
                    </div>

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="current_value" label="Current Value" placeholder="As it stands on the record" />
                        <x-registry.field name="requested_value" label="Requested Value" placeholder="What it should read" required />
                        <x-registry.field name="supporting_document" label="Supporting Document" placeholder="e.g. Affidavit reference" />
                    </div>

                    <div class="mb-3 w-full">
                        <x-registry.field name="reason" label="Reason for Amendment" rows="3" />
                    </div>

                    <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Submit Request</button>
                        <button type="reset" class="btn btn-sm btn-cta-outline px-4">Reset</button>
                    </div>
                </form>
            </x-registry.card>

            <x-registry.card title="Amendment Requests" :count="$amendments->total()">
                <x-registry.table :headings="['Staff', 'Field', 'From', 'To', 'Status', 'Reviewed By', 'Action']"
                                  empty="No amendment requests yet.">
                    @foreach ($amendments as $amendment)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">
                                {{ $amendment->staff?->surname }}, {{ $amendment->staff?->firstname }}
                                <span class="block text-base-content/50">{{ $amendment->staff?->staff_number }}</span>
                            </td>
                            <td class="px-3 py-2">{{ \App\Models\RecordAmendment::AMENDABLE_FIELDS[$amendment->field] ?? str_replace('_', ' ', $amendment->field) }}</td>
                            <td class="px-3 py-2 text-base-content/60">{{ $amendment->current_value ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $amendment->requested_value }}</td>
                            <td class="px-3 py-2">
                                @php $tone = ['approved' => 'bg-[#2E7D32]/15 text-[#1B5E20]', 'rejected' => 'bg-error/15 text-error', 'pending' => 'bg-base-300 text-base-content/70'][$amendment->status] ?? 'bg-base-300'; @endphp
                                <span class="rounded-full px-2 py-0.5 text-xs capitalize {{ $tone }}">{{ $amendment->status }}</span>
                            </td>
                            <td class="px-3 py-2">{{ $amendment->reviewed_by ?: '—' }}</td>
                            <td class="px-3 py-2">
                                @if ($amendment->is_indirect)
                                    {{-- No single column to write into: the correction is made by hand. --}}
                                    <a href="{{ route('family-referees', ['staff' => $amendment->basic_information_ulid]) }}"
                                       class="text-[#2E7D32] hover:underline">Open Family & Referees</a>
                                @endif

                                @if ($amendment->status === 'pending')
                                    @if (\App\Models\RecordAmendment::mayApprove(auth()->user()))
                                        <div class="mt-1 flex items-center gap-2">
                                            <form method="POST" action="{{ route('record-amendments.review', $amendment->ulid) }}">
                                                @csrf
                                                <input type="hidden" name="decision" value="approved">
                                                <button type="submit" class="text-[#2E7D32] hover:underline">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('record-amendments.review', $amendment->ulid) }}">
                                                @csrf
                                                <input type="hidden" name="decision" value="rejected">
                                                <button type="submit" class="text-error hover:underline">Reject</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="mt-1 block text-base-content/40">Awaiting HOD GAP's decision.</span>
                                    @endif
                                @else
                                    <span class="text-base-content/40">{{ $amendment->reviewed_at ?: '—' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-registry.table>

                <x-registry.paginator :items="$amendments" />
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
