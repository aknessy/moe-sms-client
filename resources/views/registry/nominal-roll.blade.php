<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Nominall Roll">
            <x-registry.card title="Nominall Roll"
                             description="Consolidated roll of all staff on record"
                             :count="$staff->count()">
                <div class="mb-3 flex items-center justify-between">
                    <input type="search" id="rollFilter" placeholder="Filter by name, computer number or state"
                           class="w-full max-w-sm bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md px-3 py-2 focus:outline-none focus:border-primary hover:border-primary/50" />
                    <button type="button" onclick="window.print()" class="btn btn-sm btn-cta-outline px-4">Print</button>
                </div>

                <x-registry.table :headings="['S/N', 'Computer No.', 'Surname', 'First Name', 'Other Names', 'Department', 'Designation', 'GL', 'Date of Birth', 'State of Origin', 'LGA']"
                                  empty="No staff records captured yet.">
                    @foreach ($staff as $index => $person)
                        <tr class="roll-row hover:bg-base-200/60">
                            <td class="px-3 py-2 text-base-content/60">{{ $index + 1 }}</td>
                            <td class="px-3 py-2 font-medium text-base-content">{{ $person->computer_number }}</td>
                            <td class="px-3 py-2">{{ $person->surname }}</td>
                            <td class="px-3 py-2">{{ $person->firstname }}</td>
                            <td class="px-3 py-2">{{ $person->othernames }}</td>
                            <td class="px-3 py-2">{{ $person->department ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->designation ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->grade_level ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $person->date_of_birth }}</td>
                            <td class="px-3 py-2">{{ $person->state_of_origin }}</td>
                            <td class="px-3 py-2">{{ $person->lga_of_origin }}</td>
                        </tr>
                    @endforeach
                </x-registry.table>
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>

<script>
    document.getElementById('rollFilter')?.addEventListener('input', function (event) {
        const term = event.target.value.toLowerCase();

        document.querySelectorAll('.roll-row').forEach(function (row) {
            row.classList.toggle('hidden', !row.textContent.toLowerCase().includes(term));
        });
    });
</script>
