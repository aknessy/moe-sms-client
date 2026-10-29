<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Retirement Projection" crumb="Reports & Analytics">
            <x-registry.card title="Officers Falling Due"
                             description="Serving officers only, each measured against whichever route reaches them first — 35 years of service or age 60.">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <form method="GET" action="{{ route('retirement-projection') }}"
                          class="flex flex-wrap items-center gap-2">
                        <label for="window" class="text-xs text-base-content/60">Looking ahead</label>
                        <select id="window" name="window" onchange="this.form.submit()"
                                class="min-w-[11rem] cursor-pointer rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 focus:border-primary focus:outline-none hover:border-primary/50">
                            @foreach ($windows as $months => $label)
                                <option value="{{ $months }}" @selected($window === $months)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>

                    <a href="{{ route('retirement-projection.export', ['window' => $window]) }}"
                       class="btn btn-sm btn-cta-outline inline-flex items-center gap-2 px-4">
                        <x-report.download-icon />
                        Download
                    </a>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-stat.card label="Already overdue" :value="$overdue"
                                 hint="past their date, still on the roll" tone="error" />
                    <x-stat.card label="Within 12 months" :value="$withinYear"
                                 hint="falling due this year" tone="warning" />
                    <x-stat.card label="Later in the window" :value="$laterOn"
                                 hint="beyond the next 12 months" tone="green" />
                </div>
            </x-registry.card>

            <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <x-report.breakdown title="By Year" heading="Year" :rows="$byYear"
                                    description="How the workload falls across the window"
                                    empty="Nobody falls due in this window." />
                <x-report.breakdown title="By Mode of Retirement" heading="Mode" :rows="$byMode"
                                    description="Which route carries each officer out"
                                    empty="Nobody falls due in this window." />
            </div>

            <div class="mb-4">
                <x-report.breakdown title="By Department" heading="Department" :rows="$byDepartment"
                                    description="Where the Commission will need to backfill"
                                    empty="Nobody falls due in this window." />
            </div>

            <x-registry.card title="Who Falls Due" :count="$rows->count()">
                <x-registry.table :headings="['Staff', 'File / Computer No.', 'Department', 'Designation', 'Date of Birth', 'Date of Employment', 'Mode', 'Retires On', 'Time Left']"
                                  empty="No serving officer falls due within this window.">
                    @foreach ($rows as $row)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">{{ $row->staff->surname }}, {{ $row->staff->firstname }}</td>
                            <td class="px-3 py-2">{{ $row->staff->staff_number }}</td>
                            <td class="px-3 py-2">{{ $row->staff->department ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $row->staff->designation ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $row->staff->date_of_birth }}</td>
                            <td class="px-3 py-2">{{ $row->staff->date_of_employment ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $row->mode ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $row->retires_on->toDateString() }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded-full px-2 py-0.5 text-xs {{ $row->months_left < 0 ? 'bg-error/15 text-error' : ($row->months_left <= 12 ? 'bg-warning/20 text-warning-content' : 'bg-[#2E7D32]/10 text-[#1B5E20]') }}">
                                    @if ($row->months_left < 0)
                                        {{ abs($row->months_left) }} {{ Str::plural('month', abs($row->months_left)) }} overdue
                                    @else
                                        {{ $row->months_left }} {{ Str::plural('month', $row->months_left) }}
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </x-registry.table>
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
