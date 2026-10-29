<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Nominall Roll">
            <x-registry.card title="Nominall Roll"
                             description="Officers in active service, senior grade levels first"
                             :count="$staff->total()">
                <x-registry.search :action="route('nominal-roll')" :value="$search" :total="$staff->total()"
                                  placeholder="Search by name, file or computer number, department or state">
                    <button type="button" onclick="window.print()" class="btn btn-sm btn-cta-outline px-4">Print</button>
                </x-registry.search>

                {{-- More columns than fit, so the table scrolls sideways.
                     Rank and designation are the same thing; the roll calls it
                     rank. --}}
                <x-registry.table wide
                                  :headings="['S/N', 'File / Computer No.', 'Surname', 'First Name', 'Other Names', 'Gender',
                                              'Department', 'Rank', 'GL / Step', 'Date of Birth', 'Date of Confirmation',
                                              'Phone Number', 'State of Origin', 'LGA of Origin', 'LGA of Posting',
                                              'PFA', 'RSA PIN', 'Bank & Account Number', 'Year of Retirement',
                                              'Mode of Retirement']"
                                  empty="No staff records captured yet.">
                    @foreach ($staff as $index => $person)
                        <tr class="hover:bg-base-200/60">
                            {{-- Serial continues across pages rather than restarting at 1. --}}
                            <td class="px-3 py-2 text-base-content/60">{{ $staff->firstItem() + $index }}</td>
                            <td class="px-3 py-2 font-medium text-base-content">{{ $person->staff_number }}</td>
                            <td class="px-3 py-2">{{ $person->surname }}</td>
                            <td class="px-3 py-2">{{ $person->firstname }}</td>
                            <td class="px-3 py-2">{{ $person->othernames }}</td>
                            <td class="px-3 py-2">{{ $person->gender ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->department ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->rank ?: '—' }}</td>
                            <td class="px-3 py-2">{{ trim(($person->grade_level ?: '—') . ($person->step ? ' / ' . $person->step : '')) }}</td>
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
                        </tr>
                    @endforeach
                </x-registry.table>

                <x-registry.paginator :items="$staff" />
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
