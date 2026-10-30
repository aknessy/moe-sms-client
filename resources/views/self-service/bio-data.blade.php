<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <x-registry.page title="Bio-data" crumb="My Records">
            <x-registry.card title="My Record"
                             description="As the Registry holds it. To correct anything here, raise it with the Registry.">

                @php
                    // Grouped the way the record is read, rather than the way
                    // the columns happen to sit on the table.
                    $sections = [
                        'Identity' => [
                            'Surname' => $staff->surname,
                            'First Name' => $staff->firstname,
                            'Other Names' => $staff->othernames,
                            'Gender' => $staff->gender,
                            'Date of Birth' => $staff->date_of_birth,
                        ],
                        'Service' => [
                            'File Number' => $staff->file_number,
                            'Computer Number' => $staff->computer_number,
                            'Department' => $staff->department,
                            'Division' => $staff->division,
                            'Rank' => $staff->rank,
                            'Grade Level' => $staff->grade_level_label,
                            'Step' => $staff->step_label,
                            'Date of Employment' => $staff->date_of_employment,
                            'Date of Confirmation' => $staff->date_of_confirmation,
                        ],
                        'Posting' => [
                            'State' => $posting['state'],
                            'LGA' => $posting['lga'],
                            'Duty Station' => $posting['station'],
                        ],
                        'Origin' => [
                            'State of Origin' => $staff->state?->name,
                            'LGA of Origin' => $staff->lga?->lga_name,
                        ],
                        'Contact' => [
                            'Phone Number' => $staff->phone_number,
                            'Email' => $staff->email,
                            'Address' => $staff->address,
                        ],
                        'Pension' => [
                            'PFA' => $staff->pfa,
                            'RSA PIN' => $staff->rsa,
                            'NHIS' => $staff->nhis,
                        ],
                    ];
                @endphp

                @foreach ($sections as $heading => $fields)
                    <div class="py-3 {{ ! $loop->last ? 'border-b border-base-200' : '' }}">
                        <h4 class="mt-0 mb-2 text-xs font-semibold uppercase tracking-wide text-base-content/50">
                            {{ $heading }}
                        </h4>
                        <dl class="grid grid-cols-1 gap-x-8 gap-y-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($fields as $label => $value)
                                <div>
                                    <dt class="text-xs text-base-content/50">{{ $label }}</dt>
                                    <dd class="mt-0.5 ml-0 text-sm text-base-content">
                                        {{ filled($value) ? $value : '—' }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endforeach
            </x-registry.card>
        </x-registry.page>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
