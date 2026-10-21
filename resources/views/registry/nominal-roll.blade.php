<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Nominall Roll">
            <x-registry.card title="Nominall Roll"
                             description="Consolidated roll of all staff on record"
                             :count="$staff->total()">
                <x-registry.search :action="route('nominal-roll')" :value="$search" :total="$staff->total()"
                                  placeholder="Search by name, file or computer number, department or state">
                    <button type="button" onclick="window.print()" class="btn btn-sm btn-cta-outline px-4">Print</button>
                </x-registry.search>

                <x-registry.table :headings="['S/N', 'File / Computer No.', 'Surname', 'First Name', 'Other Names', 'Department', 'Designation', 'GL / Step', 'Date of Birth', 'State of Origin', 'LGA']"
                                  empty="No staff records captured yet.">
                    @foreach ($staff as $index => $person)
                        <tr class="hover:bg-base-200/60">
                            {{-- Serial continues across pages rather than restarting at 1. --}}
                            <td class="px-3 py-2 text-base-content/60">{{ $staff->firstItem() + $index }}</td>
                            <td class="px-3 py-2 font-medium text-base-content">{{ $person->staff_number }}</td>
                            <td class="px-3 py-2">{{ $person->surname }}</td>
                            <td class="px-3 py-2">{{ $person->firstname }}</td>
                            <td class="px-3 py-2">{{ $person->othernames }}</td>
                            <td class="px-3 py-2">{{ $person->department ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->designation ?: '—' }}</td>
                            <td class="px-3 py-2">{{ trim(($person->grade_level ?: '—') . ($person->step ? ' / ' . $person->step : '')) }}</td>
                            <td class="px-3 py-2">{{ $person->date_of_birth }}</td>
                            <td class="px-3 py-2">{{ $person->state?->name ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->lga?->lga_name ?: '—' }}</td>
                        </tr>
                    @endforeach
                </x-registry.table>

                <x-registry.paginator :items="$staff" />
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
