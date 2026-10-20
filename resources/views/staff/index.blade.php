<x-dashboard pageTitle="{{ $page_title }}" :segments="$uri_segments">
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

                <form method="POST" action="{{ route('create') }}" class="flex w-full flex-col gap-y-4 py-4 lg:flex-row lg:gap-x-8 lg:gap-y-0">
                    @csrf

                    <input type="hidden" name="form-type" value="basic-information" />

                    <div class="w-full flex flex-col lg:w-1/3">
                        <div class="w-full flex flex-col bg-white px-4 py-4 border border-base-300 rounded-md">
                            <x-staff-sidebar activePage="{{ $active_page }}" :completed="$completed" :draft="$draft" />
                        </div>
                    </div>
                    <div class="w-full lg:w-2/3 px-4 py-2 bg-white border border-base-300 rounded-lg">
                        <div class="flex flex-col items-start border-b border-b-brown-100">
                            <h2 class="font-alkalami text-base font-semibold mb-0 text-base-content/80">Basic Information</h2>
                            <p class="font-normal font-sans text-sm text-base-content/60 m-0">Create a new staff by filling their basic information</p>
                        </div>
                        <div class="py-4 block bg-contain bg-right-top bg-no-repeat" style="background-image: url('{{ Vite::asset('resources/images/bg-state-empty.png')}}');background-size: 300px">
                            <div class="form-input-area py-3 px-3 mt-3 gap-y-4">
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full">
                                        <label class="font-normal font-sans text-sm text-base-content">Establishment/Computer Number</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="computer-number" placeholder="Computer Number" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">Surname</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="last-name" placeholder="Surname" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">First name</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="first-name" placeholder="First Name" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">Other names</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="other-names" placeholder="Other Names" value />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">Department</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="department" placeholder="e.g. Voter Education" value="" />
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
                                        <label class="font-normal font-sans text-sm text-base-content">Division / Unit</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="division" placeholder="e.g. Calabar HQ" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
                                                <path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">Designation</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="designation" placeholder="e.g. Administrative Officer I" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"></path>
                                                <path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2"></path>
                                                <path d="M12 12l0 .01"></path>
                                                <path d="M3 13a20 20 0 0 0 18 0"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">Grade Level</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="grade-level" placeholder="e.g. GL 08" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M3 12h4l3 8l4 -16l3 8h4"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full">
                                        <label class="font-normal font-sans text-sm text-base-content">Date of Birth</label>
                                        <div class="relative">
                                            <input type="date" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="date-of-birth" placeholder="YYYY/MM/DD" value="" />
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">Phone Number</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="phone-number" placeholder="Surname" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M3 3m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"></path>
                                                <path d="M8 4l2 0"></path>
                                                <path d="M9 17l0 .01"></path>
                                                <path d="M21 6l-2 3l2 3l-2 3l2 3"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">Email</label>
                                        <div class="relative">
                                            <input type="email" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="email" placeholder="Email address" value="{{ old('email') }}" />
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
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-normal font-sans text-sm text-base-content">State of Origin</label>
                                        <div class="relative">
                                            <select class="w-full bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="state-of-origin">
                                                <option value="brazil">Select Option</option>
                                            </select>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M9 18l3 3l3 -3"></path>
                                                <path d="M12 15v6"></path>
                                                <path d="M15 6l-3 -3l-3 3"></path>
                                                <path d="M12 3v6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-normal font-sans text-sm text-base-content">Local Government of Origin</label>
                                        <div class="relative">
                                            <select class="w-full bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="lga-of-origin">
                                                <option value="brazil">Select Option</option>
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
                                        <label class="font-normal font-sans text-sm text-base-content">Pension Fund Administrator</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="pfa" placeholder="PFA" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M9 21v-6a2 2 0 0 1 2 -2h1.341"></path>
                                                <path d="M19.682 10.682l-7.682 -7.682l-9 9h2v7a2 2 0 0 0 2 2h5"></path>
                                                <path d="M22 16c0 4 -2.5 6 -3.5 6s-3.5 -2 -3.5 -6c1 0 2.5 -.5 3.5 -1.5c1 1 2.5 1.5 3.5 1.5z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-normal font-sans text-sm text-base-content">Retirement Savings Account No.</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="rsa" placeholder="Rsa" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M7 3m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z"></path>
                                                <path d="M17 17v2a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h2"></path>
                                                <path d="M15 14v-8l-4 6h5"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full">
                                        <label class="font-normal font-sans text-sm text-base-content">NHIS No.</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="nhis" placeholder="NHIS" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                <path d="M17 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                                <path d="M9 11a5 5 0 1 0 3.95 7.95"></path>
                                                <path d="M19 20l-4 -5h-4l3 -5l-4 -3l-4 1"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                    <div class="w-full">
                                        <label class="font-normal font-sans text-sm text-base-content">Permanent Home Address</label>
                                        <div class="relative">
                                            <textarea name="address" class="h-full min-h-[100px] w-full resize-none rounded-[7px] border border-base-300 bg-white px-3 py-2.5 font-sans text-xs font-normal text-base-content outline outline-0 transition-all focus:border-primary focus:outline-0 disabled:resize-none disabled:border-0 disabled:bg-base-200" placeholder="Permanent Address">{{ old('address') }}</textarea>
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
    $(function(){

    })
</script>
