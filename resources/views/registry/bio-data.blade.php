<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Staff Bio-data">
            <x-registry.card title="Staff Bio-data"
                             description="Biographical record held for each member of staff"
                             :count="$staff->count()">
                <x-registry.table :headings="['Computer No.', 'Name', 'Department', 'Division', 'Designation', 'GL', 'Date of Birth', 'Phone', 'State / LGA', '']"
                                  empty="No staff records captured yet.">
                    @foreach ($staff as $person)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">{{ $person->computer_number }}</td>
                            <td class="px-3 py-2">{{ $person->surname }}, {{ $person->firstname }} {{ $person->othernames }}</td>
                            <td class="px-3 py-2">{{ $person->department ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->division ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->designation ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->grade_level ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->date_of_birth }}</td>
                            <td class="px-3 py-2">{{ $person->phone_number }}</td>
                            <td class="px-3 py-2">{{ $person->state_of_origin }} / {{ $person->lga_of_origin }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('record-of-service', ['staff' => $person->ulid]) }}"
                                   class="text-[#2E7D32] hover:underline">Record of Service</a>
                            </td>
                        </tr>
                    @endforeach
                </x-registry.table>
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
