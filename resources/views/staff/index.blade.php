<x-dashboard :pageTitle="$page_title" :segments="$uri_segments">
    <x-slot name="slot">
        <div class="w-full flex flex-col bg-transparent">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <nav aria-label="breadcrumb" class="w-full sm:w-1/3 space-y-2">
                    <ol class="flex w-full flex-wrap items-center gap-1.5 py-2 text-xs">
                        <li class="flex cursor-pointer items-center gap-1.5 text-base-content/60 transition-colors duration-300 hover:text-[#2E7D32]">
                            <a href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                </svg>
                            </a>
                            <span class="pointer-events-none text-base-content/30">/</span>
                        </li>
                        <li class="flex items-center text-base-content/60 transition-colors duration-300 cursor-not-allowed">
                            <a href="#">Profile Settings</a>
                        </li>
                    </ol>
                </nav>
                <div class="w-full sm:w-2/3">
                    <a href="#" class="flex items-center justify-end space-x-3 py-2 px-4 text-center text-base-content/60 text-sm hover:text-[#2E7D32] hover:underline transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                            <path d="M3 17l9 5l9 -5v-3l-9 5l-9 -5v-3l9 5l9 -5v-3l-9 5l-9 -5l9 -5l5.418 3.01"></path>
                        </svg>
                        <span class="">Upload Bulk</span>
                    </a>
                </div>
            </div>
            <div>
                    @if (session('status'))
                        <div class="mb-3 rounded-md border border-[#2E7D32]/40 bg-[#2E7D32]/10 px-4 py-3 text-sm text-[#1B5E20]">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-3 rounded-md border border-error/40 bg-error/10 px-4 py-3 text-sm text-error">
                            <p class="m-0 font-semibold">Please correct the following:</p>
                            <ul class="mt-1 list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                <form method="POST" action="{{ route('create') }}" class="flex w-full flex-col gap-y-4 py-4">
                    @csrf

                    <input type="hidden" name="form-type" value="basic-information" />

                    <div class="w-full bg-white px-4 py-3 border border-base-300 rounded-md">
                        <x-staff-sidebar activePage="{{ $active_page }}" :completed="$completed" :draft="$draft" />
                    </div>
                    <div class="w-full px-4 py-2 bg-white border border-base-300 rounded-lg">
                        <div class="flex flex-col items-start border-b border-b-brown-100">
                            <h2 class="font-alkalami text-base font-semibold mb-0 text-base-content/80">Basic Information</h2>
                            <p class="font-normal font-sans text-sm text-base-content/60 m-0">Create a new staff by filling their basic information</p>
                        <p class="mt-1 mb-0 text-xs text-base-content/60"><span class="text-error">*</span> Required field.</p>
                        </div>
                        <div class="py-4 block bg-contain bg-right-top bg-no-repeat" style="background-image: url('{{ Vite::asset('resources/images/bg-state-empty.png')}}');background-size: 300px">
                            <div class="form-input-area py-3 px-3 mt-3 gap-y-4">
                                {{-- An officer is identified by either number; whichever is
                                     supplied, the other may be left blank. --}}
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-1/2">
                                        <label class="font-normal font-sans text-sm text-base-content">File Number<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="file-number" placeholder="File Number" value="{{ old('file-number') }}"  required/>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-1/2">
                                        <label class="font-normal font-sans text-sm text-base-content">Establishment/Computer Number</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="computer-number" placeholder="Computer Number" value="{{ old('computer-number') }}" />
                                        </div>
                                    </div>
                                </div>
                                <p class="-mt-2 mb-3 text-xs text-base-content/50">The establishment/computer number is optional; older records are filed under it instead.</p>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">Surname<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="last-name" placeholder="Surname" value=""  required/>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">First name<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="first-name" placeholder="First Name" value=""  required/>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">Other names</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="other-names" placeholder="Other Names" value="{{ old('other-names') }}" />
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">Gender<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <select class="w-full bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="gender" required>
                                                <option value="">Select Option</option>
                                                @foreach (\App\Models\BasicInformation::GENDERS as $gender)
                                                    <option value="{{ $gender }}" @selected(old('gender') === $gender)>{{ $gender }}</option>
                                                @endforeach
                                            </select>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M9 18l3 3l3 -3"></path>
                                                <path d="M12 15v6"></path>
                                                <path d="M15 6l-3 -3l-3 3"></path>
                                                <path d="M12 3v6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">Department<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="department" placeholder="e.g. Voter Education" value=""  required/>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M3 21l18 0"></path>
                                                <path d="M9 8l1 0"></path>
                                                <path d="M9 12l1 0"></path>
                                                <path d="M9 16l1 0"></path>
                                                <path d="M14 8l1 0"></path>
                                                <path d="M14 12l1 0"></path>
                                                <path d="M14 16l1 0"></path>
                                                <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">Division / Unit<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="division" placeholder="e.g. Calabar HQ" value=""  required/>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">Rank / Designation<span class="text-error">*</span></label>
                                        <div class="relative">
                                            {{-- Stored as `designation`; the nominal roll reads it as rank. --}}
                                            <select class="w-full bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="designation" required>
                                                <option value="">Select Option</option>
                                                @foreach (\App\Models\BasicInformation::RANKS as $rank)
                                                    <option value="{{ $rank }}" @selected(old('designation') === $rank)>{{ $rank }}</option>
                                                @endforeach
                                            </select>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M9 18l3 3l3 -3"></path>
                                                <path d="M12 15v6"></path>
                                                <path d="M15 6l-3 -3l-3 3"></path>
                                                <path d="M12 3v6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-1/3">
                                        <label class="font-normal font-sans text-sm text-base-content">Grade Level<span class="text-error">*</span></label>
                                        <div class="relative">
                                            {{-- The number alone. Leave entitlement follows
                                                 the grade level, so it has to be one. --}}
                                            <input type="number" step="1" min="{{ \App\Models\BasicInformation::GRADE_LEVELS[0] }}" max="{{ \App\Models\BasicInformation::GRADE_LEVELS[1] }}" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="grade-level" placeholder="e.g. 8" value="{{ old('grade-level') }}"  required/>
                                        </div>
                                        <p class="mt-1 mb-0 text-xs text-base-content/50">
                                            Decides the officer's leave entitlement:
                                            1&ndash;3 &rarr; 14 days, 4&ndash;6 &rarr; 21 days, 7 and above &rarr; 30 days.
                                        </p>
                                    </div>
                                    <div class="w-full max-w-1/3">
                                        <label class="font-normal font-sans text-sm text-base-content">Step<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <input type="number" step="1" min="1" max="15" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="step" placeholder="e.g. 3" value="{{ old('step') }}"  required/>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-1/2">
                                        <label class="font-normal font-sans text-sm text-base-content">Date of Birth<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <input type="date" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="date-of-birth" placeholder="YYYY/MM/DD" value="{{ old('date-of-birth') }}"  required/>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-1/2">
                                        <label class="font-normal font-sans text-sm text-base-content">Date of Employment<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <input type="date" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="date-of-employment" placeholder="YYYY/MM/DD" value="{{ old('date-of-employment') }}"  required/>
                                        </div>
                                        <p class="mt-1 mb-0 text-xs text-base-content/50">
                                            Joined this Commission. With the date of birth it decides whether the officer
                                            leaves on 35 years of service or at 60 — whichever comes first.
                                        </p>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-1/2">
                                        <label class="font-normal font-sans text-sm text-base-content">Date of Confirmation</label>
                                        <div class="relative">
                                            <input type="date" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="date-of-confirmation" placeholder="YYYY/MM/DD" value="{{ old('date-of-confirmation') }}" />
                                        </div>
                                        <p class="mt-1 mb-0 text-xs text-base-content/50">
                                            When the appointment was confirmed. Leave it blank if the officer is still
                                            on probation — recording the confirmation later fills it in.
                                        </p>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-1/2">
                                        <label class="font-normal font-sans text-sm text-base-content">Phone Number<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="phone-number" placeholder="Phone Number" value="{{ old('phone-number') }}"  required/>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M3 3m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"></path>
                                                <path d="M8 4l2 0"></path>
                                                <path d="M9 17l0 .01"></path>
                                                <path d="M21 6l-2 3l2 3l-2 3l2 3"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-1/2">
                                        <label class="font-normal font-sans text-sm text-base-content">Email<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <input type="email" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="email" placeholder="Email address" value="{{ old('email') }}"  required/>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M3 9l9 6l9 -6l-9 -6l-9 6"></path>
                                                <path d="M21 9v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10"></path>
                                                <path d="M3 19l6 -6"></path>
                                                <path d="M15 13l6 6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-1/2 min-w-[200px]">
                                        <label class="font-normal font-sans text-sm text-base-content">State of Origin<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <select id="state-of-origin" class="w-full bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="state-of-origin" required>
                                                <option value="">Select Option</option>
                                                @foreach ($states as $state)
                                                    <option value="{{ $state->id }}" @selected(old('state-of-origin') == $state->id)>{{ $state->name }}</option>
                                                @endforeach
                                            </select>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M9 18l3 3l3 -3"></path>
                                                <path d="M12 15v6"></path>
                                                <path d="M15 6l-3 -3l-3 3"></path>
                                                <path d="M12 3v6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-1/2 min-w-[200px]">
                                        <label class="font-normal font-sans text-sm text-base-content">Local Government of Origin<span class="text-error">*</span></label>
                                        <div class="relative">
                                            {{-- Populated from the chosen state; see the script at the foot of this view. --}}
                                            <select id="lga-of-origin" class="w-full bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow-md appearance-none cursor-pointer disabled:bg-base-200 disabled:cursor-not-allowed" name="lga-of-origin" data-selected="{{ old('lga-of-origin') }}" disabled required>
                                                <option value="">Select a state first</option>
                                            </select>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M9 18l3 3l3 -3"></path>
                                                <path d="M12 15v6"></path>
                                                <path d="M15 6l-3 -3l-3 3"></path>
                                                <path d="M12 3v6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-1/3">
                                        <label class="font-normal font-sans text-sm text-base-content">Pension Fund Administrator<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="pfa" placeholder="PFA" value=""  required/>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-1/3">
                                        <label class="font-normal font-sans text-sm text-base-content">Retirement Savings Account No.<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="rsa" placeholder="Rsa" value=""  required/>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-1/3">
                                        <label class="font-normal font-sans text-sm text-base-content">NHIS No.<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="nhis" placeholder="NHIS" value=""  required/>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full">
                                        <label class="font-normal font-sans text-sm text-base-content">Permanent Home Address<span class="text-error">*</span></label>
                                        <div class="relative">
                                            <textarea name="address" class="h-full min-h-[100px] w-full resize-none rounded-[7px] border border-base-300 bg-white px-3 py-2.5 font-sans text-xs font-normal text-base-content outline outline-0 transition-all focus:border-primary focus:outline-0 disabled:resize-none disabled:border-0 disabled:bg-base-200" placeholder="Permanent Address" required>{{ old('address') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="py-3 px-4 flex flex-wrap items-center justify-end gap-2">
                                    <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">
                                        Save & Continue
                                    </button>
                                    <button type="reset" class="btn btn-sm btn-cta-outline px-4">
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
<script>
    $(function () {
        // Every LGA grouped by state, so choosing a state filters the second
        // select without a request.
        const lgasByState = @json($lgasByState);

        const $state = $('#state-of-origin');
        const $lga = $('#lga-of-origin');

        function fillLgas(stateId, preselect) {
            const lgas = lgasByState[stateId] || [];

            $lga.empty().prop('disabled', lgas.length === 0);

            if (lgas.length === 0) {
                $lga.append($('<option>', { value: '', text: 'Select a state first' }));
                return;
            }

            $lga.append($('<option>', { value: '', text: 'Select Option' }));

            lgas.forEach(function (lga) {
                $lga.append($('<option>', {
                    value: lga.id,
                    text: lga.name,
                    selected: String(lga.id) === String(preselect),
                }));
            });
        }

        $state.on('change', function () {
            fillLgas($(this).val(), null);
        });

        // Restore both after a failed submission.
        if ($state.val()) {
            fillLgas($state.val(), $lga.data('selected'));
        }
    })
</script>
