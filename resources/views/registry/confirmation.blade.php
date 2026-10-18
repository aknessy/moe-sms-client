<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Confirmation of Appointment">
            <x-registry.card title="Record a Confirmation"
                             description="Movement from probationary to confirmed appointment">
                <form method="POST" action="{{ route('confirmation.store') }}">
                    @csrf

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="basic_information_ulid" label="Staff" :options="$staffOptions" required />
                        <x-registry.field name="appointment_date" label="Date of First Appointment" type="date" required />
                        <x-registry.field name="confirmation_date" label="Date Confirmed" type="date" />
                    </div>

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="grade_level" label="Grade Level" placeholder="e.g. GL 08" />
                        <x-registry.field name="step" label="Step" placeholder="e.g. Step 3" />
                        <x-registry.field name="status" label="Status" required :options="[
                            'pending' => 'Pending',
                            'confirmed' => 'Confirmed',
                            'deferred' => 'Deferred',
                        ]" />
                    </div>

                    <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                        <x-registry.field name="reference_number" label="Reference Number" placeholder="Confirmation letter reference" />
                    </div>

                    <div class="mb-3 w-full">
                        <x-registry.field name="remarks" label="Remarks" rows="3" />
                    </div>

                    <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                        <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Save Confirmation</button>
                        <button type="reset" class="btn btn-sm btn-cta-outline px-4">Reset</button>
                    </div>
                </form>
            </x-registry.card>

            <x-registry.card title="Confirmation Records" :count="$confirmations->count()">
                <x-registry.table :headings="['Staff', 'Appointed', 'Confirmed', 'Grade / Step', 'Status', 'Reference']"
                                  empty="No confirmation records yet.">
                    @foreach ($confirmations as $confirmation)
                        <tr class="hover:bg-base-200/60">
                            <td class="px-3 py-2 font-medium text-base-content">
                                {{ $confirmation->staff?->surname }}, {{ $confirmation->staff?->firstname }}
                                <span class="block text-base-content/50">{{ $confirmation->staff?->computer_number }}</span>
                            </td>
                            <td class="px-3 py-2">{{ $confirmation->appointment_date }}</td>
                            <td class="px-3 py-2">{{ $confirmation->confirmation_date ?: '—' }}</td>
                            <td class="px-3 py-2">{{ trim(($confirmation->grade_level ?: '—') . ' / ' . ($confirmation->step ?: '—')) }}</td>
                            <td class="px-3 py-2">
                                @php $tone = ['confirmed' => 'bg-[#2E7D32]/15 text-[#1B5E20]', 'deferred' => 'bg-warning/20 text-warning-content', 'pending' => 'bg-base-300 text-base-content/70'][$confirmation->status] ?? 'bg-base-300'; @endphp
                                <span class="rounded-full px-2 py-0.5 text-xs capitalize {{ $tone }}">{{ $confirmation->status }}</span>
                            </td>
                            <td class="px-3 py-2">{{ $confirmation->reference_number ?: '—' }}</td>
                        </tr>
                    @endforeach
                </x-registry.table>
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
