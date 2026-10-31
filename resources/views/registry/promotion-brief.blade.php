<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Promotion Brief">

            <x-registry.card title="Promotion Exercise Year"
                             :description="'An officer falls due ' . $yearsOnGrade . ' years after their last promotion — or, where they have never been promoted, after their appointment was confirmed.'">
                <form method="GET" action="{{ route('promotion-brief') }}"
                      class="flex w-full flex-col gap-4 py-3 sm:flex-row sm:items-end">
                    <div class="w-full sm:max-w-xs">
                        <x-registry.field name="year" label="Year" :value="$year"
                                          :options="collect($years)->mapWithKeys(fn ($y) => [$y => $y])->all()" />
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center gap-2 pb-0.5">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Show Brief</button>
                    </div>
                </form>
            </x-registry.card>

            {{-- Who the records say is due but who is not yet on the brief.
                 Recording them is what turns the calculation into a document. --}}
            @if ($candidates->isNotEmpty())
                <x-registry.card title="Due for Promotion in {{ $year }}" :count="$candidates->count()"
                                 description="Worked out from the records, and not yet recorded on the brief">
                    <x-registry.table wide
                                      :headings="['S/N', 'Staff Name', 'Department', 'Rank', 'Grade / Step', 'Last Moved', 'Falls Due']"
                                      empty="Nobody falls due in {{ $year }}.">
                        @foreach ($candidates as $index => $row)
                            <tr class="hover:bg-base-200/60">
                                <td class="px-3 py-2 text-base-content/60">{{ $index + 1 }}</td>
                                <td class="px-3 py-2 font-medium text-base-content">
                                    {{ trim($row['staff']->surname . ', ' . trim($row['staff']->firstname . ' ' . $row['staff']->othernames)) }}
                                    <span class="block text-base-content/50">{{ $row['staff']->staff_number }}</span>
                                </td>
                                <td class="px-3 py-2">{{ $row['staff']->department ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row['staff']->rank ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row['staff']->grade_step }}</td>
                                <td class="px-3 py-2">{{ $row['last_moved_on']?->toDateString() ?: '—' }}</td>
                                <td class="px-3 py-2">
                                    {{ $row['due_on']->toDateString() }}
                                    @if ($row['overdue'])
                                        <span class="ml-1 rounded-full border border-error/40 bg-error/10 px-2 py-0.5 text-xs text-error">
                                            Overdue
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-registry.table>

                    <form method="POST" action="{{ route('promotion-brief.store') }}"
                          class="flex flex-wrap items-center justify-end gap-2 py-3">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}" />
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
                            Record All on the {{ $year }} Brief
                        </button>
                    </form>
                </x-registry.card>
            @endif

            <x-registry.card title="The {{ $year }} Brief" :count="$recorded->count()"
                             description="Recorded as it stood when the brief was drawn, longest-standing first">

                {{-- Added by hand, for an officer the rule misses. --}}
                <form method="POST" action="{{ route('promotion-brief.entry') }}"
                      class="mb-4 flex flex-col gap-4 rounded-md border border-base-300 bg-base-200/40 px-4 py-3 sm:flex-row sm:items-end">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}" />

                    <div class="min-w-0 flex-1">
                        <x-registry.picker name="basic_information_ulid" label="Add an Officer"
                                           description="For an officer who should be on the brief but whom the records do not show as due."
                                           :options="$staffOptions" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <x-registry.field name="remarks" label="Remarks"
                                          placeholder="Why they are being added" />
                    </div>

                    <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md shrink-0">Add</button>
                </form>

                <x-registry.table wide
                                  :headings="['S/N', 'Staff Name', 'Rank', 'Grade / Step', 'Last Moved', 'Fell Due', 'Status', 'Action']"
                                  empty="Nothing has been recorded on the {{ $year }} brief yet.">
                    @foreach ($recorded as $index => $entry)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 text-base-content/60">{{ $index + 1 }}</td>
                            <td class="px-3 py-2 font-medium text-base-content">
                                {{ trim($entry->staff?->surname . ', ' . trim($entry->staff?->firstname . ' ' . $entry->staff?->othernames)) }}
                                <span class="block text-base-content/50">{{ $entry->staff?->staff_number }}</span>
                            </td>
                            <td class="px-3 py-2">{{ $entry->designation ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $entry->grade_step }}</td>
                            <td class="px-3 py-2">{{ $entry->last_moved_on ?: '—' }}</td>
                            <td class="px-3 py-2">
                                {{ $entry->due_on ?: '—' }}
                                @if ($entry->is_overdue)
                                    <span class="ml-1 rounded-full border border-error/40 bg-error/10 px-2 py-0.5 text-xs text-error">
                                        Overdue
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <x-status-pill :status="$entry->status"
                                               :label="$statuses[$entry->status] ?? 'Pending'" />
                                @if (filled($entry->remarks))
                                    <span class="block mt-1 text-xs text-base-content/50">{{ $entry->remarks }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-1">
                                    {{-- Where the entry stands once the exercise has been held. --}}
                                    <form method="POST" action="{{ route('promotion-brief.review', $entry->ulid) }}"
                                          class="flex items-center gap-1">
                                        @csrf
                                        <select name="status"
                                                class="cursor-pointer rounded-md border border-base-300 bg-white px-2 py-1 text-xs text-base-content/80 focus:outline-none focus:border-primary">
                                            @foreach ($statuses as $value => $label)
                                                <option value="{{ $value }}" @selected($entry->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit"
                                                class="rounded-md border border-base-300 px-2 py-1 text-xs text-base-content/70 hover:border-primary hover:text-primary">
                                            Save
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('promotion-brief.remove', $entry->ulid) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-md border border-base-300 px-2 py-1 text-xs text-base-content/50 hover:border-error hover:text-error">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-registry.table>
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
