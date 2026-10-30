<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Performance Appraisal" crumb="My Records">

            {{-- Who they are, across the top. --}}
            <div class="w-full mb-4 px-4 py-4 bg-white border border-base-300 rounded-lg">
                <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-center">
                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-full border border-base-300 bg-base-200">
                        @if ($staff->has_photo)
                            <img src="{{ route('my-photo.show') }}" alt="Passport photograph"
                                 class="h-full w-full object-cover" />
                        @else
                            <div class="flex h-full w-full items-center justify-center text-xs text-base-content/40">
                                No photo
                            </div>
                        @endif
                    </div>
                    <div class="text-center sm:text-left">
                        <h3 class="m-0 font-sans text-lg font-bold text-base-content">
                            {{ trim($staff->surname . ', ' . trim($staff->firstname . ' ' . $staff->othernames)) }}
                        </h3>
                        <p class="m-0 text-sm text-base-content/70">{{ $staff->rank ?: 'Rank not recorded' }}</p>
                        <p class="m-0 text-xs text-base-content/50">
                            {{ $staff->staff_number ?: 'No staff number' }}
                            @if ($staff->department) &middot; {{ $staff->department }} @endif
                        </p>
                    </div>
                </div>
            </div>

            <x-registry.card title="Overall Performance"
                             description="Assessed by the Head of Department">
                @if ($appraisal === null)
                    <div class="py-8 text-center">
                        <p class="m-0 text-sm text-base-content/60">
                            You have not been assessed yet.
                        </p>
                        <p class="mt-1 mb-0 text-xs text-base-content/40">
                            Your appraisal is set by the Head of Department (GAP), or by whoever
                            is assigned to it, and will appear here once it has been recorded.
                        </p>
                    </div>
                @else
                    <p class="py-6 text-center text-3xl font-semibold text-[#1B5E20]">{{ $appraisal }}</p>
                @endif
            </x-registry.card>

            <x-registry.card title="My Standing"
                             description="As the Registry holds it">
                @php
                    // Everything an officer should be able to read off at a
                    // glance, in the order it matters to them.
                    $facts = [
                        'Grade Level' => $staff->grade_level_label,
                        'Step' => $staff->step_label,
                        'Date of Employment' => $staff->date_of_employment,
                        'Date of Confirmation' => $staff->date_of_confirmation,
                        'Date of Retirement' => $retiresOn?->toDateString(),
                        'Mode of Retirement' => $staff->mode_of_retirement_label,
                        'Current Posting (State)' => $posting['state'],
                        'Current Posting (LGA)' => $posting['lga'],
                        'Duty Station' => $posting['station'],
                        'Leave Entitlement' => $staff->leaveDays() === null
                            ? null : $staff->leaveDays() . ' days a year',
                    ];
                @endphp

                <dl class="grid grid-cols-1 gap-x-8 gap-y-3 py-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($facts as $label => $value)
                        <div class="border-b border-base-200 pb-2">
                            <dt class="text-xs uppercase tracking-wide text-base-content/50">{{ $label }}</dt>
                            <dd class="mt-0.5 ml-0 text-sm font-medium text-base-content">
                                {{ filled($value) ? $value : '—' }}
                            </dd>
                        </div>
                    @endforeach
                </dl>

                @if ($retiresOn !== null)
                    <p class="mt-2 mb-1 text-xs text-base-content/50">
                        {{-- Said in words as well as a date, which is the part
                             that actually answers the question. --}}
                        That is {{ $retiresOn->isPast() ? 'already past' : $retiresOn->diffForHumans(['parts' => 2]) }}.
                    </p>
                @endif
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
