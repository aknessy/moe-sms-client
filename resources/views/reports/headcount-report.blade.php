<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Headcount & Audit" crumb="Reports & Analytics">
            <x-registry.card title="Headcount"
                             description="Everyone on record, and where they stand.">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-stat.card label="On record" :value="$total" hint="every staff record held" />
                    <x-stat.card label="In active service" :value="$active" hint="neither retired nor deceased" tone="green" />
                    <x-stat.card label="Retired" :value="$retired" hint="left on retirement" tone="warning" />
                    <x-stat.card label="Deceased" :value="$deceased" hint="died in service" tone="error" />
                </div>
            </x-registry.card>

            <x-registry.card title="Headcount by Department" :count="$byDepartment->count()"
                             description="Serving strength against the total held, department by department.">
                <x-registry.table :headings="['Department', 'On record', 'In active service', 'Retired', 'Deceased']"
                                  empty="No staff on record.">
                    @foreach ($byDepartment as $name => $counts)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">{{ $name }}</td>
                            <td class="px-3 py-2">{{ $counts->total }}</td>
                            <td class="px-3 py-2 text-[#1B5E20]">{{ $counts->active }}</td>
                            <td class="px-3 py-2">{{ $counts->retired ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $counts->deceased ?: '—' }}</td>
                        </tr>
                    @endforeach
                </x-registry.table>
            </x-registry.card>

            <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <x-report.breakdown title="By Division / Unit" heading="Division" :rows="$byDivision"
                                    description="Where officers are posted"
                                    empty="No staff on record." />
                <x-report.breakdown title="Exits on File" heading="Exit type" :rows="$byExitType"
                                    description="How officers have left the service"
                                    empty="No exits recorded." />
            </div>

            <x-registry.card title="Verification & Audit"
                             description="Records that are incomplete or inconsistent. Each line is something a registry officer can act on.">
                <div class="mb-3 flex justify-end">
                    <a href="{{ route('headcount-report.export') }}"
                       class="btn btn-sm btn-cta-outline inline-flex items-center gap-2 px-4">
                        <x-report.download-icon />
                        Download Findings
                    </a>
                </div>

                <x-registry.table :headings="['Finding', 'Records', 'Why it matters', 'Who']"
                                  empty="Nothing to report.">
                    @foreach ($findings as $finding)
                        <tr class="hover:bg-base-200/60 align-top">
                            <td class="px-3 py-2 font-medium text-base-content">{{ $finding->finding }}</td>
                            <td class="px-3 py-2">
                                @if ($finding->count === 0)
                                    <span class="rounded-full bg-[#2E7D32]/10 px-2 py-1 text-xs text-[#1B5E20]">None</span>
                                @elseif ($finding->informational ?? false)
                                    <span class="rounded-full bg-base-200 px-2 py-1 text-xs text-base-content/70">{{ $finding->count }}</span>
                                @else
                                    <span class="rounded-full bg-error/10 px-2 py-1 text-xs text-error">{{ $finding->count }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-base-content/60">{{ $finding->why }}</td>
                            <td class="px-3 py-2">
                                @if ($finding->count === 0)
                                    <span class="text-base-content/40">—</span>
                                @else
                                    {{-- The first few by name, so the finding can be acted on
                                         without opening the spreadsheet. --}}
                                    <span class="text-base-content/70">
                                        {{ $finding->staff->take(3)->map(fn ($s) => trim($s->surname . ', ' . $s->firstname))->implode('; ') }}
                                        @if ($finding->count > 3)
                                            <span class="text-base-content/40">and {{ $finding->count - 3 }} more</span>
                                        @endif
                                    </span>
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
