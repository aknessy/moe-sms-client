<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Query Response">
            @php
                $staff = $query->staff;
                $fullName = trim("{$staff?->surname}, {$staff?->firstname} {$staff?->othernames}");
                $responded = $query->response_date ? \Carbon\Carbon::parse($query->response_date) : null;
            @endphp

            <div class="mb-4 flex flex-wrap items-center justify-between gap-2 print:hidden">
                <a href="{{ route('query-discipline') }}" class="text-xs text-base-content/60 hover:text-[#2E7D32]">
                    &larr; Back to Query &amp; Discipline
                </a>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($query->decision)
                        @php $tone = $query->decision === 'satisfactory' ? 'bg-[#2E7D32]/15 text-[#1B5E20]' : 'bg-warning/20 text-warning-content'; @endphp
                        <span class="rounded-full px-3 py-1 text-xs capitalize {{ $tone }}">
                            {{ str_replace('-', ' ', $query->decision) }} &middot; {{ $query->decision_date }}
                        </span>
                    @endif
                    <button type="button" onclick="window.print()" class="btn btn-sm btn-cta-outline px-4">Print</button>
                </div>
            </div>

            {{-- ---------------------------------------------------------------
                 The response, set out as the official letter it stands in for
                 --------------------------------------------------------------- --}}
            <div class="w-full mb-4 bg-white border border-base-300 rounded-lg px-8 py-8 lg:px-14 lg:py-12">
                <div class="flex items-center justify-center gap-4 border-b-2 border-[#2E7D32] pb-5">
                    <img src="{{ Vite::asset('resources/images/inec_nig_logo.png') }}" alt="" class="h-14 w-14 object-contain" />
                    <div class="text-center">
                        <p class="m-0 font-alkalami text-base font-semibold uppercase tracking-wide text-base-content">
                            Independent National Electoral Commission
                        </p>
                        <p class="m-0 text-xs text-base-content/60">Cross River State Office &middot; Human Resources Department</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-4 text-xs sm:flex-row sm:justify-between">
                    <dl class="m-0 space-y-1">
                        <div class="flex gap-2">
                            <dt class="text-base-content/60">Our Ref:</dt>
                            <dd class="m-0 font-medium text-base-content">{{ $query->reference_number ?: '—' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-base-content/60">File / Computer No:</dt>
                            <dd class="m-0 font-medium text-base-content">{{ $staff?->staff_number ?: '-' }}</dd>
                        </div>
                    </dl>
                    <dl class="m-0 space-y-1 sm:text-right">
                        <div class="flex gap-2 sm:justify-end">
                            <dt class="text-base-content/60">Date of Query:</dt>
                            <dd class="m-0 font-medium text-base-content">{{ \Carbon\Carbon::parse($query->query_date)->format('j F Y') }}</dd>
                        </div>
                        <div class="flex gap-2 sm:justify-end">
                            <dt class="text-base-content/60">Date Responded:</dt>
                            <dd class="m-0 font-medium text-base-content">{{ $responded?->format('j F Y') ?: 'Not yet responded' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="mt-7 text-xs leading-6">
                    <p class="m-0 font-medium text-base-content">{{ $fullName ?: 'Unknown officer' }}</p>
                    <p class="m-0 text-base-content/70">{{ $department ?: 'Department not on record' }}</p>
                    <p class="m-0 text-base-content/70">{{ $division ?: 'Division not on record' }}</p>
                    <p class="m-0 text-base-content/70">{{ $staff?->address ?: 'Address not on record' }}</p>
                    <p class="m-0 text-base-content/70">{{ $staff?->phone_number }}@if($staff?->email) &middot; {{ $staff->email }}@endif</p>
                </div>

                <p class="mt-7 mb-0 text-xs font-semibold uppercase tracking-wide text-base-content">
                    Re: Response to Query &mdash; {{ $query->offence }}
                </p>

                <p class="mt-5 mb-0 text-xs text-base-content">Sir/Madam,</p>

                <div class="mt-3 text-xs leading-7 text-base-content whitespace-pre-line">
                    @if ($query->response)
                        {{ $query->response }}
                    @else
                        <span class="text-base-content/50 italic">
                            No written response has been received from the officer for this query.
                        </span>
                    @endif
                </div>

                <div class="mt-10 text-xs">
                    <p class="m-0 text-base-content">Yours faithfully,</p>
                    <div class="mt-10 w-64 border-t border-base-content/50 pt-1">
                        <p class="m-0 font-medium text-base-content">{{ $fullName ?: '—' }}</p>
                        <p class="m-0 text-base-content/60">{{ $staff?->staff_number }}</p>
                        <p class="m-0 text-base-content/60">{{ $department ?: '—' }}</p>
                    </div>
                </div>

                @if ($query->decision)
                    <div class="mt-10 border-t border-dashed border-base-300 pt-5 text-xs">
                        <p class="m-0 font-semibold uppercase tracking-wide text-base-content/60">Minute of the Reviewing Officer</p>
                        <p class="mt-2 mb-0 capitalize text-base-content">{{ str_replace('-', ' ', $query->decision) }}@if($query->sanction && $query->sanction !== 'none') &mdash; sanction: {{ $query->sanction }}@endif</p>
                        @if ($query->decision_remarks)
                            <p class="mt-1 mb-0 whitespace-pre-line text-base-content/80">{{ $query->decision_remarks }}</p>
                        @endif
                        <div class="mt-8 w-64 border-t border-base-content/50 pt-1">
                            <p class="m-0 font-medium text-base-content">{{ $query->decision_by }}</p>
                            <p class="m-0 text-base-content/60">{{ $query->decision_date }}</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Particulars of the original query --}}
            <x-registry.card title="Particulars of the Query">
                <dl class="grid grid-cols-1 gap-x-8 gap-y-3 text-xs sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-base-content/60">Offence</dt>
                        <dd class="m-0 font-medium text-base-content">{{ $query->offence }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/60">Status</dt>
                        <dd class="m-0 font-medium capitalize text-base-content">{{ $query->status }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/60">Sanction</dt>
                        <dd class="m-0 font-medium capitalize text-base-content">{{ $query->sanction ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/60">Reference</dt>
                        <dd class="m-0 font-medium text-base-content">{{ $query->reference_number ?: '—' }}</dd>
                    </div>
                </dl>
                @if ($query->details)
                    <p class="mt-4 mb-0 whitespace-pre-line text-xs leading-6 text-base-content/80">{{ $query->details }}</p>
                @endif
            </x-registry.card>

            {{-- Outcome --}}
            <x-registry.card title="Record an Outcome"
                             description="How the Commission treats the officer's response">
                <form method="POST" action="{{ route('query-response.decide', $query->ulid) }}" class="print:hidden">
                    @csrf

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="decision_by" label="Reviewing Officer" placeholder="Name and designation"
                                          :value="$query->decision_by" />
                        <x-registry.field name="sanction" label="Sanction (if any)" :value="$query->sanction" :options="[
                            'none' => 'None',
                            'caution' => 'Caution',
                            'warning' => 'Warning',
                            'suspension' => 'Suspension',
                            'demotion' => 'Demotion',
                            'dismissal' => 'Dismissal',
                        ]" />
                    </div>

                    <div class="mb-4 w-full">
                        <x-registry.field name="decision_remarks" label="Minute / Remarks" rows="3"
                                          :value="$query->decision_remarks"
                                          placeholder="The reviewing officer's minute on this response" />
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" name="decision" value="satisfactory" class="btn btn-sm btn-cta px-4 shadow-md">
                            Response Satisfactory
                        </button>
                        <button type="submit" name="decision" value="further-action" class="btn btn-sm btn-cta-outline px-4">
                            Further Action Required
                        </button>
                        <button type="submit" name="decision" value="panel" class="btn btn-sm btn-cta-outline px-4">
                            Refer to Disciplinary Panel
                        </button>
                        <button type="submit" name="decision" value="sanctioned" class="btn btn-sm btn-outline btn-error px-4">
                            Apply Sanction
                        </button>
                    </div>
                    <p class="mt-3 mb-0 text-xs text-base-content/60">
                        Marking a response satisfactory closes the query and clears any sanction.
                    </p>
                </form>
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
