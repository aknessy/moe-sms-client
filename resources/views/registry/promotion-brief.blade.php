<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Promotion Brief">

            {{-- Everything above the brief is for working the list up: it is
                 the brief itself that goes on paper. --}}
            <div class="print-hide">
                <x-registry.card title="Promotion Exercise Year"
                                 :description="$bands . ', counted from their last promotion — or, where they have never been promoted, from the day their appointment was confirmed.'">
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

                {{-- The list as it is being worked up. Opening the year puts
                     everyone the records say is due in here; the rest is added
                     by hand. Nothing here is on the brief until the button
                     below moves it there. --}}
                <x-registry.card title="Add an Officer" :count="$staged->count()"
                                 description="Officers being considered for the {{ $year }} exercise. Nobody is on the brief until they are added to it.">

                    <form method="POST" action="{{ route('promotion-brief.entry') }}"
                          class="mb-4 flex flex-col gap-4 rounded-md border border-base-300 bg-base-200/40 px-4 py-3 sm:flex-row sm:items-end">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}" />

                        <div class="min-w-0 flex-1">
                            <x-registry.picker name="basic_information_ulid" label="Officer"
                                               description="For an officer who should be considered but whom the records do not show as due."
                                               :options="$staffOptions" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <x-registry.field name="remarks" label="Remarks"
                                              placeholder="Why they are being added" />
                        </div>

                        <button type="submit" class="btn btn-sm btn-cta-outline px-4 shrink-0">Add</button>
                    </form>

                    <x-registry.table wide
                                      :headings="['S/N', 'Staff Name', 'Department', 'Rank', 'Grade / Step', 'Last Moved', 'Falls Due', 'Action']"
                                      empty="Nobody is being considered for the {{ $year }} exercise.">
                        @foreach ($staged as $index => $entry)
                            <tr class="hover:bg-base-200/60">
                                <td class="px-3 py-2 text-base-content/60">{{ $index + 1 }}</td>
                                <td class="px-3 py-2 font-medium text-base-content">
                                    {{ trim($entry->staff?->surname . ', ' . trim($entry->staff?->firstname . ' ' . $entry->staff?->othernames)) }}
                                    <span class="block text-base-content/50">{{ $entry->staff?->staff_number }}</span>
                                </td>
                                <td class="px-3 py-2">{{ $entry->staff?->department ?: '—' }}</td>
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
                                    @if (filled($entry->remarks))
                                        <span class="block mt-1 text-xs text-base-content/50">{{ $entry->remarks }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <form method="POST" action="{{ route('promotion-brief.remove', $entry->ulid) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-md border border-base-300 px-2 py-1 text-xs text-base-content/50 hover:border-error hover:text-error">
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </x-registry.table>

                    {{-- Bottom right, so it reads as what becomes of the table
                         above it. --}}
                    <form method="POST" action="{{ route('promotion-brief.store') }}"
                          class="flex flex-wrap items-center justify-end gap-2 py-3">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}" />

                        @if ($staged->isEmpty())
                            <p class="m-0 mr-auto text-xs text-base-content/50">
                                Nobody is waiting to be added to the {{ $year }} brief.
                            </p>
                        @endif

                        <button type="submit" @disabled($staged->isEmpty())
                                class="btn btn-sm px-4 {{ $staged->isEmpty()
                                    ? 'btn-cta-outline cursor-not-allowed opacity-50'
                                    : 'btn-cta shadow-md' }}">
                            Add to Promotion Brief
                        </button>
                    </form>
                </x-registry.card>
            </div>

            {{-- The brief itself, and the only part of the page that goes on
                 paper. Empty until the list above is added to it. --}}
            <div class="hidden print:block">
                <h1 class="m-0 text-center font-alkalami text-lg font-bold uppercase tracking-wide">
                    {{ \App\Models\Setting::get('organisation.name') }}
                </h1>
                @if (filled(\App\Models\Setting::get('organisation.command')))
                    <p class="m-0 text-center text-sm font-semibold uppercase">
                        {{ \App\Models\Setting::get('organisation.command') }}
                    </p>
                @endif
                <h2 class="mt-3 mb-1 text-center text-sm font-bold uppercase underline">
                    Promotion Brief &mdash; {{ $year }}
                </h2>
                <p class="mt-0 mb-4 text-center text-xs">
                    {{ $bands }}.
                    {{ $brief->count() . ' officer' . ($brief->count() === 1 ? '' : 's') . ' listed.' }}
                </p>
            </div>

            {{-- The card escapes its title, so the dash is the character
                 rather than the entity. --}}
            <x-registry.card title="Promotion Brief — {{ $year }}" :count="$brief->count()"
                             description="As it goes on paper, longest-standing first">

                @if ($brief->isNotEmpty())
                    <div class="flex flex-wrap items-center justify-end gap-2 pt-3 print-hide">
                        <button type="button" onclick="window.print()"
                                class="btn btn-sm btn-cta px-4 shadow-md">Print the Brief</button>
                    </div>
                @endif

                <x-registry.table wide
                                  :headings="['S/N', 'Staff Name', 'Department', 'Rank', 'Grade / Step', 'Last Moved', 'Fell Due']"
                                  empty="Nothing has been added to the {{ $year }} brief yet.">
                    @foreach ($brief as $index => $entry)
                        <tr>
                            <td class="px-3 py-2 text-base-content/60">{{ $index + 1 }}</td>
                            <td class="px-3 py-2 font-medium text-base-content">
                                {{ trim($entry->staff?->surname . ', ' . trim($entry->staff?->firstname . ' ' . $entry->staff?->othernames)) }}
                                <span class="block text-base-content/50">{{ $entry->staff?->staff_number }}</span>
                            </td>
                            <td class="px-3 py-2">{{ $entry->staff?->department ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $entry->designation ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $entry->grade_step }}</td>
                            <td class="px-3 py-2">{{ $entry->last_moved_on ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $entry->due_on ?: '—' }}</td>
                        </tr>
                    @endforeach
                </x-registry.table>
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
