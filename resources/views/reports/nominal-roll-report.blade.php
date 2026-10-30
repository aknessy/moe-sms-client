<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Nominal Roll Report" crumb="Reports & Analytics">
            <x-registry.card title="Narrow the Roll"
                             description="The whole roll unless narrowed. Every figure and table below reflects the filters in force.">
                <form method="GET" action="{{ route('nominal-roll-report') }}">
                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="department" label="Department"
                                          :value="$filters['department'] ?? null"
                                          :options="$departments->mapWithKeys(fn ($d) => [$d => $d])->all()" />
                        {{-- Held as a number, offered as "GL 09". --}}
                        <x-registry.field name="grade_level" label="Grade Level"
                                          :value="$filters['grade_level'] ?? null"
                                          :options="$gradeLevels->mapWithKeys(fn ($g) => [$g => 'GL ' . str_pad((string) $g, 2, '0', STR_PAD_LEFT)])->all()" />
                        <x-registry.field name="state" label="State of Origin"
                                          :value="$filters['state'] ?? null"
                                          :options="$states->all()" />
                        <x-registry.field name="status" label="Status"
                                          :value="$filters['status'] ?? null"
                                          :options="$statuses" />
                    </div>

                    <div class="py-2 flex flex-wrap items-center justify-end gap-2">
                        @if (collect($filters)->filter()->isNotEmpty())
                            <a href="{{ route('nominal-roll-report') }}"
                               class="text-xs text-base-content/60 hover:text-[#2E7D32]">Clear filters</a>
                        @endif
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Apply</button>
                        <a href="{{ route('nominal-roll-report.export', request()->query()) }}"
                           class="btn btn-sm btn-cta-outline inline-flex items-center gap-2 px-4">
                            <x-report.download-icon />
                            Download
                        </a>
                    </div>
                </form>
            </x-registry.card>

            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat.card label="On the roll" :value="$total" hint="matching the filters" tone="green" />
                <x-stat.card label="Departments" :value="$byDepartment->count()" hint="represented" />
                <x-stat.card label="Grade levels" :value="$byGradeLevel->count()" hint="represented" />
                <x-stat.card label="States of origin" :value="$byState->count()" hint="represented" />
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <x-report.breakdown title="By Department" heading="Department" :rows="$byDepartment"
                                    description="Where the roll sits across the Commission"
                                    empty="No staff match these filters." />
                <x-report.breakdown title="By Grade Level" heading="Grade level" :rows="$byGradeLevel"
                                    description="The shape of the establishment"
                                    empty="No staff match these filters." />
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <x-report.breakdown title="By State of Origin" heading="State" :rows="$byState"
                                    description="Federal character across the roll"
                                    empty="No staff match these filters." />
                <x-report.breakdown title="By LGA of Origin" heading="LGA" :rows="$byLga"
                                    description="The ten LGAs most represented"
                                    empty="No staff match these filters." />
            </div>

            <x-registry.card title="The Roll" :count="$staff->total()">
                {{-- The same columns the Registry roll carries, plus the status
                     this report filters on. More than fit, so it scrolls
                     sideways. Rank and designation are the same thing; the
                     roll calls it rank. --}}
                <x-registry.table wide
                                  :headings="['File / Computer No.', 'Name', 'Gender', 'Department', 'Division', 'Rank',
                                              'GL / Step', 'Date of Birth', 'Date of Confirmation', 'Phone Number',
                                              'State of Origin', 'LGA of Origin', 'LGA of Posting', 'PFA', 'RSA PIN',
                                              'Bank & Account Number', 'Year of Retirement', 'Mode of Retirement', 'Status']"
                                  empty="No staff match these filters.">
                    @foreach ($staff as $person)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">{{ $person->staff_number }}</td>
                            <td class="px-3 py-2">{{ trim($person->surname . ', ' . trim($person->firstname . ' ' . $person->othernames)) }}</td>
                            <td class="px-3 py-2">{{ $person->gender ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->department ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->division ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->rank ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->grade_step }}</td>
                            <td class="px-3 py-2">{{ $person->date_of_birth }}</td>
                            <td class="px-3 py-2">{{ $person->date_of_confirmation ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->phone_number ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->state?->name ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->lga?->lga_name ?: '—' }}</td>
                            {{-- Where their postings have left them, not where
                                 they come from. --}}
                            <td class="px-3 py-2">{{ $postingContext[$person->ulid]['lga'] ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $person->pfa ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->rsa ?: '—' }}</td>
                            {{-- Blank until the Finance/Account section exists
                                 to hold it. --}}
                            <td class="px-3 py-2 text-base-content/30">—</td>
                            <td class="px-3 py-2">{{ $person->retiresOn()?->year ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->mode_of_retirement_label ?: '—' }}</td>
                            <td class="px-3 py-2">
                                @if ($person->is_deceased)
                                    <span class="rounded-full bg-error/10 px-2 py-1 text-xs text-error">Deceased</span>
                                @elseif ($person->is_retired)
                                    <span class="rounded-full bg-warning/20 px-2 py-1 text-xs text-warning-content">Retired</span>
                                @else
                                    <span class="rounded-full bg-[#2E7D32]/10 px-2 py-1 text-xs text-[#1B5E20]">Active</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-registry.table>

                <x-registry.paginator :items="$staff" />
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
