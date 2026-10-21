<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <div class="w-full flex flex-col bg-transparent">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <nav aria-label="breadcrumb" class="w-full sm:w-1/3">
                    <ol class="flex w-full flex-wrap items-center gap-1.5 py-2 text-xs">
                        <li class="flex items-center gap-1.5 text-base-content/60">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                            </svg>
                            <span class="pointer-events-none text-base-content/30">/</span>
                        </li>
                        <li class="flex items-center text-base-content/60">Dashboard</li>
                    </ol>
                </nav>
                <div class="w-full sm:w-2/3">
                    <h1 class="font-alkalami text-sm font-semibold uppercase tracking-wide flex justify-end m-0">Staff Management &mdash; Statistics</h1>
                </div>
            </div>

            {{-- Headline figures --}}
            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat.card label="In Active Service" :value="$totals['active']" tone="green"
                             hint="{{ $totals['staff'] }} on record" :href="route('nominal-roll')" />
                <x-stat.card label="Retired" :value="$totals['retired']"
                             hint="{{ $totals['exited'] }} total exits" :href="route('retirement-exit')" />
                <x-stat.card label="Departments" :value="$totals['departments']"
                             hint="{{ $totals['duty_stations'] }} duty stations on postings" />
                <x-stat.card label="Divisions / Units" :value="$totals['units']"
                             hint="across all departments" />
            </div>

            {{-- Things that need attention --}}
            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat.card label="Queries Issued" :value="$totals['queries']"
                             hint="{{ $totals['open_queries'] }} still open" :href="route('query-discipline')" />
                <x-stat.card label="Disciplinary Actions" :value="$totals['sanctions']"
                             tone="{{ $totals['sanctions'] > 0 ? 'error' : 'default' }}"
                             hint="sanctions applied" :href="route('query-discipline')" />
                <x-stat.card label="Due to Retire" :value="$totals['due_within_a_year']"
                             tone="{{ $totals['due_within_a_year'] > 0 ? 'warning' : 'default' }}"
                             hint="within the next 12 months" :href="route('retirement-exit')" />
                <x-stat.card label="Awaiting Review" :value="$totals['pending_amendments'] + $totals['pending_confirmations']"
                             hint="{{ $totals['pending_amendments'] }} amendments, {{ $totals['pending_confirmations'] }} confirmations"
                             :href="route('record-amendments')" />
            </div>

            {{-- Charts --}}
            <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <x-registry.card title="Service Status"
                                 description="Officers in active service against those who have left">
                    <x-stat.donut :segments="$serviceSplit" centreLabel="On record" />
                </x-registry.card>

                <x-registry.card title="Retirements: {{ $previousYear }} vs {{ $currentYear }}"
                                 description="Exits already recorded against those falling due at age 60">
                    <x-stat.bars :groups="collect($retirementTrend)->map(fn ($row) => [
                        'label' => $row['year'],
                        'bars' => [
                            ['label' => 'Recorded', 'value' => $row['recorded'], 'colour' => '#2E7D32'],
                            ['label' => 'Due at 60', 'value' => $row['due'], 'colour' => '#A5703A'],
                        ],
                    ])->all()" />
                </x-registry.card>
            </div>

            {{-- Departments and units --}}
            <x-registry.card title="Departments &amp; Units"
                             description="Units operating within each department, with headcount"
                             :count="$departmentRows->count()">
                <x-registry.table :headings="['Department', 'Units', 'Unit Names', 'Headcount', 'Active']"
                                  empty="No departments captured on staff records yet.">
                    @foreach ($departmentRows as $row)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">{{ $row->department }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded-full bg-[#2E7D32]/10 px-2 py-0.5 text-[#1B5E20]">{{ $row->units }}</span>
                            </td>
                            <td class="px-3 py-2 text-base-content/70">{{ $row->unit_names->implode(', ') ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $row->headcount }}</td>
                            <td class="px-3 py-2">{{ $row->active }}</td>
                        </tr>
                    @endforeach
                </x-registry.table>
            </x-registry.card>

            {{-- Supporting breakdowns --}}
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <x-registry.card title="Grade Level Spread" description="Officers in active service">
                    @forelse ($gradeSpread as $grade => $count)
                        @php $share = $gradeSpread->max() > 0 ? round($count / $gradeSpread->max() * 100) : 0; @endphp
                        <div class="mb-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-base-content/70">{{ $grade }}</span>
                                <span class="font-medium text-base-content">{{ $count }}</span>
                            </div>
                            <div class="mt-1 h-2 w-full rounded-full bg-base-200">
                                <div class="h-2 rounded-full bg-[#2E7D32]" style="width: {{ max(4, $share) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-base-content/60 m-0">No grade levels captured yet.</p>
                    @endforelse
                </x-registry.card>

                <x-registry.card title="Sanctions Applied" description="Outcomes of disciplinary matters">
                    @forelse ($sanctionSpread as $sanction => $count)
                        <div class="flex items-center justify-between border-b border-base-300/70 py-2 text-xs last:border-0">
                            <span class="capitalize text-base-content/70">{{ $sanction }}</span>
                            <span class="rounded-full bg-error/10 px-2 py-0.5 font-medium text-error">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-base-content/60 m-0">No sanctions on record.</p>
                    @endforelse
                </x-registry.card>

                <x-registry.card title="Next to Retire" description="Soonest first">
                    @forelse ($dueWithinAYear as $person)
                        <div class="flex items-center justify-between border-b border-base-300/70 py-2 text-xs last:border-0">
                            <span class="text-base-content/70">
                                {{ $person->surname }}, {{ $person->firstname }}
                                <span class="block text-base-content/40">{{ $person->staff_number }}</span>
                            </span>
                            <span class="font-medium text-base-content">{{ $retiresOn($person)?->toDateString() }}</span>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-base-content/60 m-0">Nobody falls due in the next 12 months.</p>
                    @endforelse

                    @if ($totals['average_age'])
                        <p class="mt-4 mb-0 text-xs text-base-content/50">Average age across all records: {{ $totals['average_age'] }} years</p>
                    @endif
                </x-registry.card>
            </div>
        </div>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
