<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Staff Bio-data">
            <x-registry.card title="Staff Bio-data"
                             description="Biographical record held for each member of staff"
                             :count="$staff->total()">
                <x-registry.search :action="route('bio-data')" :value="$search" :total="$staff->total()"
                                  placeholder="Search by name, file or computer number, department or grade" />

                <x-registry.table :headings="['File / Computer No.', 'Name', 'Department', 'Division', 'Designation', 'GL / Step', 'Date of Birth', 'Phone', 'State / LGA', '']"
                                  empty="No staff records captured yet.">
                    @foreach ($staff as $person)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">{{ $person->staff_number }}</td>
                            <td class="px-3 py-2">{{ $person->surname }}, {{ $person->firstname }} {{ $person->othernames }}</td>
                            <td class="px-3 py-2">{{ $person->department ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->division ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->designation ?: '—' }}</td>
                            <td class="px-3 py-2">{{ trim(($person->grade_level ?: '—') . ($person->step ? ' / ' . $person->step : '')) }}</td>
                            <td class="px-3 py-2">{{ $person->date_of_birth }}</td>
                            <td class="px-3 py-2">{{ $person->phone_number }}</td>
                            <td class="px-3 py-2">{{ $person->state?->name ?: '—' }} / {{ $person->lga?->lga_name ?: '—' }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('record-of-service', ['staff' => $person->ulid]) }}"
                                   class="text-[#2E7D32] hover:underline">Record of Service</a>
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
