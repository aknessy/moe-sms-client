{{-- The officer's service: when they came in, when they were confirmed, and
     what they hold now. Drawn from the staff record and correctable here, as
     the Bio-data section is. --}}

<form method="POST" action="{{ route('hr-appraisal.career-history') }}" class="py-3">
    @csrf
    <input type="hidden" name="staff" value="{{ $staff->ulid }}" />
    <input type="hidden" name="section" value="{{ $section }}" />

    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
        <x-registry.field name="date_of_employment" label="Date of First Appointment"
                          type="date" required :value="$staff->date_of_employment" />
        <x-registry.field name="date_of_confirmation" label="Date of Confirmation"
                          type="date" :value="$staff->date_of_confirmation" />
    </div>

    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
        <div class="w-full">
            <x-registry.field name="date_of_present_appointment" label="Date of Present Appointment"
                              type="date" :value="$presentAppointment" />
            @unless ($staff->date_of_present_appointment)
                <p class="mt-1 mb-0 text-xs text-base-content/50">
                    Taken from the officer's last promotion until it is saved.
                </p>
            @endunless
        </div>

        <div class="w-full">
            <x-registry.field name="date_of_acting_appointment"
                              label="Date of Acting Appointment"
                              type="date" :value="$staff->date_of_acting_appointment" />
            <p class="mt-1 mb-0 text-xs text-base-content/50">
                Leave blank where the officer holds no acting appointment.
            </p>
        </div>
    </div>

    {{-- Qualifications are a run of entries held against the education
         record, so they are read here and maintained where they are entered. --}}
    <div class="mb-3 w-full">
        <div class="mb-2 flex flex-wrap items-end justify-between gap-2">
            <div>
                <p class="m-0 font-sans text-sm font-medium text-base-content">
                    Qualifications (Educational / Professional / Technical)
                </p>
                <p class="mt-0.5 mb-0 text-xs text-base-content/50">
                    With the year each was obtained. Recorded under the officer's
                    educational progression.
                </p>
            </div>
        </div>

        <x-registry.table :headings="['S/N', 'Type', 'Qualification', 'Institution', 'Year Obtained']"
                          empty="No qualifications are on the officer's record.">
            @foreach ($qualifications as $index => $qualification)
                <tr class="hover:bg-base-200/60">
                    <td class="px-3 py-2 text-base-content/60">{{ $index + 1 }}</td>
                    <td class="px-3 py-2">{{ $qualification['type'] ?: '—' }}</td>
                    <td class="px-3 py-2 font-medium text-base-content">{{ $qualification['qualification'] }}</td>
                    <td class="px-3 py-2">{{ $qualification['institution'] ?: '—' }}</td>
                    <td class="px-3 py-2">{{ $qualification['year'] ?: '—' }}</td>
                </tr>
            @endforeach
        </x-registry.table>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200 py-3">
        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
            {{ $continueLabel ?? 'Save Career History' }}
        </button>
    </div>
</form>
