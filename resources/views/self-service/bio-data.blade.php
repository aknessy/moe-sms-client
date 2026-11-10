{{-- Their own record, as the Registry holds it — read only, a card at a
     time. Corrections are raised with the Registry, not typed here.

     The same page goes to paper as a curriculum vitae: the cards stay on
     the screen and the CV document below stands in for them the moment the
     page is printed, laid out for A4 and cut at section boundaries. --}}

<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <style>
            /* A4 paper, one page after another: the CV is set to the sheet,
                and the dashboard's fixed screen furniture — the sidebar, the
                title strips, the cards — does not go to paper with it.
                The sheet itself is what the command's settings hold. */
            @media print {
                @page { size: {{ $printSize }} {{ strtolower($printOrientation) }}; margin: 14mm 12mm 12mm; }

                html, body {
                    height: auto !important;
                    overflow: visible !important;
                    background: white !important;
                }

                /* The chrome around the page: the sidebar, the title strips,
                    any screen control marked out of print, and the app
                    footer beneath the CV. The content area's grey padding
                    hardens into a white sheet. */
                div.w-64, div.h-16.bg-base-100, .no-print,
                #cv ~ * { display: none !important; }
                div.h-screen { height: auto !important; min-height: 0 !important; }
                div.flex-1.bg-base-200 {
                    background: white !important;
                    padding: 0 !important;
                }

                /* The CV takes the sheet. */
                #cv { display: block !important; }

                /* A section is never cut in half at a page turn; a table's
                    heading row rides along at the top of every page it
                    reaches. */
                #cv section { break-inside: avoid; page-break-inside: avoid; }
                #cv tr { break-inside: avoid; page-break-inside: avoid; }
                #cv thead { display: table-header-group; }
            }

            /* The CV lives only on paper — the screen shows the cards. */
            #cv { display: none; }

            /* Set for the sheet, not the screen: the CV document itself. */
            #cv {
                color: #1a1a1a;
                font-family: Figtree, sans-serif;
                font-size: 10.5pt;
                line-height: 1.45;
            }
            #cv .cv-name { font-size: 16pt; font-weight: 700; margin: 0; color: #1B5E20; }
            #cv .cv-caption { font-size: 9.5pt; color: #444; margin: 2pt 0 0; }
            #cv .cv-photo { width: 88px; height: 112px; object-fit: cover; object-position: top; border: 1px solid #999; }
            #cv h2 {
                font-size: 10.5pt;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
                border-bottom: 1px solid #888;
                padding-bottom: 2pt;
                margin: 12pt 0 5pt;
                color: #1B5E20;
            }
            #cv .cv-head { border-bottom: 2px solid #1B5E20; padding-bottom: 8pt; }
            #cv section { margin-bottom: 6pt; }
            #cv dl { margin: 0; }
            #cv dt { font-size: 8pt; text-transform: uppercase; letter-spacing: .05em; color: #666; font-weight: 600; }
            #cv dd { margin: 0 0 3pt; }
            #cv table { width: 100%; border-collapse: collapse; font-size: 9pt; }
            #cv th, #cv td { border: 1px solid #aaa; padding: 3pt 5pt; text-align: left; vertical-align: top; }
            #cv th { background: #f2f2f2; font-weight: 600; }
            #cv .cv-empty { font-style: italic; color: #555; }
        </style>

        {{-- The app furniture. It stays on the screen and never reaches paper. --}}
        <div class="no-print" id="bio-data-screen">
            <x-registry.page title="Bio-data" crumb="My Records">

                {{-- The officer's face, beside who the record is of: the one
                     sheet the Registry keeps a photograph against. --}}
                <x-registry.card title="Photograph"
                                 description="What the Registry holds against your record">
                    <div class="flex flex-col items-start gap-4 py-2 sm:flex-row sm:items-center">
                        @if ($staff->has_photo)
                            {{-- Served from behind the dashboard's session rather
                                 than a public link to the disk. --}}
                            <img src="{{ route('my-photo.show') }}" alt="Your photograph"
                                 class="h-36 w-30 rounded-md border border-base-300 bg-base-200 object-cover object-top" />

                            <div class="flex flex-col gap-1">
                                <p class="m-0 text-sm text-base-content">
                                    {{ $staff->photo_name }}
                                </p>
                                <p class="m-0 text-xs text-base-content/50">
                                    To change it, replace it under Update Photo.
                                </p>
                                <a href="{{ route('my-photo') }}"
                                   class="btn btn-sm btn-cta-outline px-4 mt-1">Update Photo</a>
                            </div>
                        @else
                            <div class="flex h-36 w-30 flex-col items-center justify-center rounded-md border border-dashed border-base-300 bg-base-200/40 px-4 text-center">
                                <p class="m-0 text-xs text-base-content/50">
                                    No photograph on file yet.
                                </p>
                            </div>

                            <div class="flex flex-col gap-1">
                                <p class="m-0 text-sm text-base-content">
                                    Your record has no photograph against it.
                                </p>
                                <p class="m-0 text-xs text-base-content/50">
                                    A passport photograph is the first thing the
                                    Registry turns to when reading a file.
                                </p>
                                <a href="{{ route('my-photo') }}"
                                   class="btn btn-sm btn-cta px-4 mt-1 shadow-md">Upload Photograph</a>
                            </div>
                        @endif
                    </div>
                </x-registry.card>

                @php
                    // Grouped the way the record is read, rather than the way
                    // the columns happen to sit on the table — each one its
                    // own card, so the page reads a section at a time.
                    $sections = [
                        'Identity' => [
                            'Surname' => $staff->surname,
                            'First Name' => $staff->firstname,
                            'Other Names' => $staff->othernames,
                            'Gender' => $staff->gender,
                            'Date of Birth' => $staff->date_of_birth,
                            'Marital Status' => $staff->marital_status,
                        ],
                        'Service' => [
                            'File Number' => $staff->file_number,
                            'Computer Number' => $staff->computer_number,
                            'Department' => $staff->department,
                            'Division' => $staff->division,
                            'Unit' => $staff->unit,
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

                    // The working life: how they rose through the service, and
                    // what they left behind to join it. Promotion entries are
                    // held in the wizard's sheets, one run of rows apiece.
                    $promotions = $staff->promotion
                        ->flatMap(fn ($sheet) => $sheet->promotion_record ?? [])
                        ->sortBy('effective_from')
                        ->values();
                    $career = $staff->career;
                    // The leave they have taken, as the service history holds
                    // it and as they have applied for it through the dashboard.
                    $leaveRecorded = $staff->leave?->leave_record ?? [];
                    $leaveApplications = $staff->leaveApplications()->newestFirst()->get();
                    $children = $staff->children ?? [];
                @endphp

                @foreach ($sections as $heading => $fields)
                    <x-registry.card :title="$heading"
                                     :description="$loop->first
                                        ? 'As the Registry holds it. To correct anything, raise it with the Registry.'
                                        : ''">
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
                    </x-registry.card>
                @endforeach

                {{-- Where the Officer's career history fields exist, set them down as they are on record. --}}
                @php
                    $nextOfKins = $staff->nextOfKins;
                    $referees = $staff->referee->flatMap(fn ($sheet) => $sheet->referees_record ?? []);
                    $qualifications = $staff->qualifications();
                @endphp

                <x-registry.card title="Children"
                                 description="Those the service should know about — at most two, with the day each was born.">
                    @if (collect($children)->isEmpty())
                        <p class="text-sm text-base-content/60">
                            No child is recorded here yet.
                        </p>
                    @else
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($children as $child)
                                <div class="rounded-md border border-base-300 px-3 py-2">
                                    <p class="mb-0 text-sm font-medium text-base-content">{{ $child['name'] ?? '—' }}</p>
                                    <p class="mb-0 mt-0.5 text-xs text-base-content/60">
                                        Born {{ $child['date_of_birth'] ?? 'on a day not recorded' }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-registry.card>

                <x-registry.card title="Career History"
                                 description="How you rose through the service, and what you left behind to join it.">
                    @if ($promotions->isEmpty() && $career === null)
                        <p class="text-sm text-base-content/60">
                            No career history is recorded here yet.
                        </p>
                    @else
                        @if ($promotions->isNotEmpty())
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-base-300 text-left text-xs text-base-content/50">
                                        <th class="py-1 pr-2 font-medium">Promoted to</th>
                                        <th class="py-1 pr-2 font-medium">Grade Level</th>
                                        <th class="py-1 pr-2 font-medium">Step</th>
                                        <th class="py-1 font-medium">Effective From</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($promotions as $promotion)
                                        <tr class="border-b border-base-200 last:border-0">
                                            <td class="py-1.5 pr-2 text-base-content">
                                                {{ $promotion['designation'] ?? '—' }}</td>
                                            <td class="py-1.5 pr-2 text-base-content">
                                                GL {{ str_pad($promotion['grade_level'] ?? '', 2, '0', STR_PAD_LEFT) ?: '—' }}</td>
                                            <td class="py-1.5 pr-2 text-base-content">
                                                {{ $promotion['step'] ?? '—' }}</td>
                                            <td class="py-1.5 text-base-content">
                                                {{ $promotion['effective_from'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        @if ($career !== null)
                            <div class="rounded-md border border-base-300 px-3 py-2 {{ $promotions->isNotEmpty() ? 'mt-3' : '' }}">
                                @if ($career->no_previous_employment)
                                    <p class="mb-0 text-sm font-medium text-base-content">
                                        Joined the service straight from school.
                                    </p>
                                @else
                                    <p class="mb-0 text-sm font-medium text-base-content">
                                        Previous employment — {{ $career->rank ?: 'a rank not recorded' }},
                                        {{ $career->ministerial_dept ?: 'a ministry not recorded' }}
                                        ({{ $career->mode_of_employment ?: 'mode of employment not recorded' }}).
                                    </p>
                                    <p class="mb-0 mt-0.5 text-xs text-base-content/60">
                                        {{ $career->address_of_ministerial_dept ?: 'Address not recorded' }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    @endif
                </x-registry.card>

                <x-registry.card title="Leave History"
                                 description="The leave you have taken and the applications they answer to.">
                    @if ($leaveRecorded === [] && $leaveApplications->isEmpty())
                        <p class="text-sm text-base-content/60">
                            No leave is recorded here yet.
                        </p>
                    @else
                        @if ($leaveApplications->isNotEmpty())
                            <p class="mb-1 text-xs font-medium text-base-content/50">Applications on record</p>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-base-300 text-left text-xs text-base-content/50">
                                        <th class="py-1 pr-2 font-medium">Leave</th>
                                        <th class="py-1 pr-2 font-medium">From</th>
                                        <th class="py-1 pr-2 font-medium">Days</th>
                                        <th class="py-1 font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($leaveApplications as $application)
                                        <tr class="border-b border-base-200 last:border-0">
                                            <td class="py-1.5 pr-2 text-base-content">{{ $application->leave_type }}</td>
                                            <td class="py-1.5 pr-2 text-base-content">{{ $application->effective_date }}</td>
                                            <td class="py-1.5 pr-2 text-base-content">{{ $application->days }}</td>
                                            <td class="py-1.5 text-base-content">
                                                {{ \App\Models\LeaveApplication::STATUSES[$application->status] ?? $application->status }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        @if ($leaveRecorded !== [])
                            <p class="{{ $leaveApplications->isNotEmpty() ? 'mt-3' : '' }} mb-1 text-xs font-medium text-base-content/50">
                                Held in your service history
                            </p>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-base-300 text-left text-xs text-base-content/50">
                                        <th class="py-1 pr-2 font-medium">Leave</th>
                                        <th class="py-1 pr-2 font-medium">From</th>
                                        <th class="py-1 pr-2 font-medium">Until</th>
                                        <th class="py-1 font-medium">Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($leaveRecorded as $leave)
                                        <tr class="border-b border-base-200 last:border-0">
                                            <td class="py-1.5 pr-2 text-base-content">{{ $leave['leave_type'] ?? '—' }}</td>
                                            <td class="py-1.5 pr-2 text-base-content">{{ $leave['effective_from'] ?? '—' }}</td>
                                            <td class="py-1.5 pr-2 text-base-content">{{ $leave['expires_on'] ?? '—' }}</td>
                                            <td class="py-1.5 text-base-content">{{ $leave['duration'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    @endif
                </x-registry.card>

                <x-registry.card title="Next of Kin"
                                 description="Who the Registry turns to when you cannot be reached.">
                    @if ($nextOfKins->isEmpty())
                        <p class="text-sm text-base-content/60">
                            Nobody is recorded here yet.
                        </p>
                    @else
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($nextOfKins as $kin)
                                <div class="rounded-md border border-base-300 px-3 py-2">
                                    <p class="mb-0 text-sm font-medium text-base-content">{{ $kin->nok_name }}</p>
                                    <p class="mb-0 mt-0.5 text-xs text-base-content/60">
                                        {{ $kin->nok_relationship ?: 'Relationship not recorded' }}
                                    </p>
                                    <p class="mb-0 mt-0.5 text-xs text-base-content/60">
                                        {{ $kin->nok_address ?: 'Address not recorded' }}
                                    </p>
                                    <p class="mb-0 mt-0.5 text-xs text-base-content/60">
                                        {{ $kin->nok_phone_number ?: 'Phone not recorded' }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-registry.card>

                <x-registry.card title="Educational Qualifications"
                                 description="Certificates the record carries, oldest first.">
                    @if ($qualifications->isEmpty())
                        <p class="text-sm text-base-content/60">
                            No qualification is recorded here yet.
                        </p>
                    @else
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-base-300 text-left text-xs text-base-content/50">
                                    <th class="py-1 pr-2 font-medium">Qualification</th>
                                    <th class="py-1 pr-2 font-medium">Institution</th>
                                    <th class="py-1 pr-2 font-medium">Type</th>
                                    <th class="py-1 font-medium">Year</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($qualifications as $qualification)
                                    <tr class="border-b border-base-200 last:border-0">
                                        <td class="py-1.5 pr-2 text-base-content">
                                            {{ $qualification['qualification'] ?: '—' }}</td>
                                        <td class="py-1.5 pr-2 text-base-content">
                                            {{ $qualification['institution'] ?: '—' }}</td>
                                        <td class="py-1.5 pr-2 text-base-content">
                                            {{ $qualification['type'] ?: '—' }}</td>
                                        <td class="py-1.5 text-base-content">
                                            {{ $qualification['year'] ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </x-registry.card>

                <x-registry.card title="Referees"
                                 description="Who may be asked to speak for you.">
                    @if ($referees->isEmpty())
                        <p class="text-sm text-base-content/60">
                            No referee is recorded here yet.
                        </p>
                    @else
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($referees as $referee)
                                <div class="rounded-md border border-base-300 px-3 py-2">
                                    <p class="mb-0 text-sm font-medium text-base-content">{{ $referee['name'] ?? '—' }}</p>
                                    <p class="mb-0 mt-0.5 text-xs text-base-content/60">
                                        {{ $referee['address'] ?? 'Address not recorded' }}
                                    </p>
                                    <p class="mb-0 mt-0.5 text-xs text-base-content/60">
                                        {{ $referee['phone'] ?? 'Phone not recorded' }}
                                    </p>
                                    <p class="mb-0 mt-0.5 text-xs text-base-content/60">
                                        {{ $referee['email'] ?? 'Email not recorded' }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-registry.card>

                {{-- The one control on the page: it prints the CV document
                     below, not the cards. --}}
                <div class="flex items-center justify-between py-2">
                    <p class="m-0 text-xs text-base-content/50">
                        The record above goes to paper as a curriculum vitae, laid
                        out for A4.
                    </p>
                    <button onclick="window.print()"
                            class="btn btn-sm btn-cta px-4 shadow-md no-print">
                        Print Bio-data
                    </button>
                </div>
            </x-registry.page>
        </div>

        {{-- What goes to paper: the record, written as a curriculum vitae on
             A4 — one page running into the next, cut between sections. --}}
        <div id="cv" class="cv">
            @php
                $fullname = trim(implode(' ', array_filter([
                    $staff->surname, $staff->firstname, $staff->othernames,
                ])));
                $qualificationRows = $staff->qualifications();
                $kinRows = $staff->nextOfKins;
                $refereeRows = $staff->referee->flatMap(fn ($sheet) => $sheet->referees_record ?? []);
            @endphp

            <header class="cv-head flex items-center justify-between gap-6">
                <div>
                    <h1 class="cv-name">{{ $fullname ?: 'Name not recorded' }}</h1>
                    <p class="cv-caption">
                        {{ $staff->designation ?: 'Rank not recorded' }}
                    </p>
                    <p class="cv-caption mt-1">
                        {{ $staff->staff_number ?: 'Staff number not recorded' }}
                        &middot; {{ $staff->grade_level_label ?: 'GL not recorded' }},
                        {{ $staff->step_label ?: 'Step not recorded' }}
                    </p>
                    <p class="cv-caption">
                        {{ $staff->phone_number ?: '' }}{{ filled($staff->phone_number) && filled($staff->email) ? ' · ' : '' }}{{ $staff->email ?: '' }}
                    </p>
                </div>
                @if ($staff->has_photo)
                    {{-- Carried in the page itself, so the printed sheet
                         never has to ask the disk again. --}}
                    <img class="cv-photo" src="{{ $photoDataUri }}" alt="Passport photograph">
                @endif
            </header>

            <section>
                <h2>Personal Data</h2>
                <dl class="grid grid-cols-2 gap-x-8">
                    <div><dt>Full Name</dt><dd>{{ $fullname ?: '—' }}</dd></div>
                    <div><dt>Gender</dt><dd>{{ $staff->gender ?: '—' }}</dd></div>
                    <div><dt>Date of Birth</dt><dd>{{ $staff->date_of_birth ?: '—' }}</dd></div>
                    <div><dt>Marital Status</dt><dd>{{ $staff->marital_status ?: '—' }}</dd></div>
                    <div>
                        <dt>Address</dt>
                        <dd>{{ $staff->address ?: '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section>
                <h2>Place of Origin</h2>
                <dl class="grid grid-cols-2 gap-x-8">
                    <div><dt>State of Origin</dt><dd>{{ $staff->state?->name ?: '—' }}</dd></div>
                    <div><dt>LGA of Origin</dt><dd>{{ $staff->lga?->lga_name ?: '—' }}</dd></div>
                </dl>
            </section>

            <section>
                <h2>Children</h2>
                @if (collect($children)->isEmpty())
                    <p class="cv-empty">None recorded.</p>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th style="width: 22%">Date of Birth</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($children as $child)
                                <tr>
                                    <td>{{ $child['name'] ?? '—' }}</td>
                                    <td>{{ $child['date_of_birth'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section>
                <h2>Service Record</h2>
                <dl class="grid grid-cols-2 gap-x-8">
                    <div><dt>File Number</dt><dd>{{ $staff->file_number ?: '—' }}</dd></div>
                    <div><dt>Computer Number</dt><dd>{{ $staff->computer_number ?: '—' }}</dd></div>
                    <div><dt>Department</dt><dd>{{ $staff->department ?: '—' }}</dd></div>
                    <div><dt>Division</dt><dd>{{ $staff->division ?: '—' }}</dd></div>
                    <div><dt>Unit</dt><dd>{{ $staff->unit ?: '—' }}</dd></div>
                    <div><dt>Rank</dt><dd>{{ $staff->designation ?: '—' }}</dd></div>
                    <div><dt>Grade Level &amp; Step</dt>
                        <dd>{{ $staff->grade_level_label ?: '—' }},
                            {{ $staff->step_label ?: '—' }}</dd></div>
                    <div><dt>Date of Employment</dt><dd>{{ $staff->date_of_employment ?: '—' }}</dd></div>
                    <div><dt>Date of Confirmation</dt><dd>{{ $staff->date_of_confirmation ?: '—' }}</dd></div>
                    <div><dt>State of Deployment</dt><dd>{{ $posting['state'] ?: '—' }}</dd></div>
                    <div><dt>LGA of Deployment</dt><dd>{{ $posting['lga'] ?: '—' }}</dd></div>
                    <div><dt>Duty Station</dt><dd>{{ $posting['station'] ?: '—' }}</dd></div>
                </dl>
            </section>

            <section>
                <h2>Career History</h2>
                @if ($promotions->isEmpty() && $career === null)
                    <p class="cv-empty">None recorded.</p>
                @else
                    @if ($promotions->isNotEmpty())
                        <table>
                            <thead>
                                <tr>
                                    <th>Promoted to</th>
                                    <th>Grade Level</th>
                                    <th>Step</th>
                                    <th>Effective From</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($promotions as $promotion)
                                    <tr>
                                        <td>{{ $promotion['designation'] ?? '—' }}</td>
                                        <td>{{ $promotion['grade_level'] ?? '—' }}</td>
                                        <td>{{ $promotion['step'] ?? '—' }}</td>
                                        <td>{{ $promotion['effective_from'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    @if ($career !== null)
                        <dl class="grid grid-cols-2 gap-x-8 {{ $promotions->isNotEmpty() ? 'mt-2' : '' }}">
                            @if ($career->no_previous_employment)
                                <div style="grid-column: span 2">
                                    <dt>Previous Employment</dt>
                                    <dd>Joined the service straight from school — no previous employment on record.</dd>
                                </div>
                            @else
                                <div><dt>Previous Employment</dt>
                                    <dd>{{ $career->rank ?: '—' }},
                                        {{ $career->ministerial_dept ?: '—' }}
                                        ({{ $career->mode_of_employment ?: '—' }})</dd></div>
                                <div><dt>Address of Ministry</dt>
                                    <dd>{{ $career->address_of_ministerial_dept ?: '—' }}</dd></div>
                            @endif
                        </dl>
                        @if (filled($career->date_of_employement))
                            <dl class="grid grid-cols-2 gap-x-8">
                                <div><dt>Date of Employment</dt><dd>{{ $career->date_of_employement }}</dd></div>
                            </dl>
                        @endif
                    @endif
                @endif
            </section>

            <section>
                <h2>Educational Qualifications</h2>
                @if ($qualificationRows->isEmpty())
                    <p class="cv-empty">None recorded.</p>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Qualification</th>
                                <th>Institution</th>
                                <th>Type</th>
                                <th style="width: 12%">Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($qualificationRows as $row)
                                <tr>
                                    <td>{{ $row['qualification'] ?: '—' }}</td>
                                    <td>{{ $row['institution'] ?: '—' }}</td>
                                    <td>{{ $row['type'] ?: '—' }}</td>
                                    <td>{{ $row['year'] ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section>
                <h2>Leave History</h2>
                @if ($leaveRecorded === [] && $leaveApplications->isEmpty())
                    <p class="cv-empty">None recorded.</p>
                @else
                    @if ($leaveApplications->isNotEmpty())
                        <table>
                            <thead>
                                <tr>
                                    <th>Leave Applied For</th>
                                    <th>From</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($leaveApplications as $application)
                                    <tr>
                                        <td>{{ $application->leave_type }}</td>
                                        <td>{{ $application->effective_date }}</td>
                                        <td>{{ $application->days }}</td>
                                        <td>{{ \App\Models\LeaveApplication::STATUSES[$application->status] ?? $application->status }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    @if ($leaveRecorded !== [])
                        <table class="{{ $leaveApplications->isNotEmpty() ? 'mt-2' : '' }}">
                            <thead>
                                <tr>
                                    <th>Leave Taken</th>
                                    <th>From</th>
                                    <th>Until</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($leaveRecorded as $leave)
                                    <tr>
                                        <td>{{ $leave['leave_type'] ?? '—' }}</td>
                                        <td>{{ $leave['effective_from'] ?? '—' }}</td>
                                        <td>{{ $leave['expires_on'] ?? '—' }}</td>
                                        <td>{{ $leave['duration'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                @endif
            </section>

            <section>
                <h2>Next of Kin</h2>
                @if ($kinRows->isEmpty())
                    <p class="cv-empty">None recorded.</p>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Relationship</th>
                                <th>Address</th>
                                <th>Phone</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kinRows as $kin)
                                <tr>
                                    <td>{{ $kin->nok_name }}</td>
                                    <td>{{ $kin->nok_relationship ?: '—' }}</td>
                                    <td>{{ $kin->nok_address ?: '—' }}</td>
                                    <td>{{ $kin->nok_phone_number ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section>
                <h2>Referees</h2>
                @if ($refereeRows->isEmpty())
                    <p class="cv-empty">None recorded.</p>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Phone</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($refereeRows as $row)
                                <tr>
                                    <td>{{ $row['name'] ?? '—' }}</td>
                                    <td>{{ $row['address'] ?? '—' }}</td>
                                    <td>{{ $row['phone'] ?? '—' }}</td>
                                    <td>{{ $row['email'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        </div>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
