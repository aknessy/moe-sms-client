<x-dashboard :segments="$uri_segments" pageTitle="{{ $page_title }}">
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
                            <a href="#">Career</a>
                        </li>
                    </ol>
                </nav>
                <div class="w-full sm:w-2/3">
                    <a href="#" class="flex items-center justify-end space-x-3 py-2 px-4 text-center text-base-content/60 text-xs hover:text-[#2E7D32] hover:underline transition-all">
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

                    <input type="hidden" name="form-type" value="work-career" />

                    <div class="w-full bg-white px-4 py-3 border border-base-300 rounded-md">
                        <x-staff-sidebar activePage="{{ $active_page }}" :completed="$completed" :draft="$draft" />
                    </div>
                    <div class="w-full px-4 py-2 bg-white border border-base-300 rounded-lg">
                        <div class="flex flex-col items-start border-b border-b-brown-100">
                            <h2 class="font-alkalami text-base font-semibold mb-0 text-base-content/80">Working Career</h2>
                            <p class="font-sans text-sm font-normal text-base-content/60 m-0">Service record</p>
                        <p class="mt-1 mb-0 text-xs text-base-content/60">Every field here is optional — leave the section blank if this post is the officer's first job.</p>
                        </div>
                        <div class="py-4 block bg-contain bg-right-top bg-no-repeat" style="background-image: url('{{Vite::asset('resources/images/bg-state-empty.png')}}');background-size: 300px">
                            <div class="form-input-area py-3 px-3 mt-3 gap-y-4">
                                <div class="w-full">
                                    <div id="cloneable" class="w-full relative mb-3">
                                        <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                            <div class="w-full">
                                                <label class="font-normal font-sans text-sm text-base-content">Date of Employment</label>
                                                <div class="relative">
                                                    <input type="date" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="date-of-employment" placeholder="YYYY/MM/DD" value="" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                            <div class="w-full min-w-max">
                                                <label class="font-normal font-sans text-sm text-base-content">Rank</label>
                                                <div class="relative">
                                                    <input type="text" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="rank" placeholder="Rank at Employment" value="" />
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                        <path d="M4 5m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"></path>
                                                        <path d="M4 15l16 0"></path>
                                                        <path d="M4 19l16 0"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="w-full min-w-max">
                                                <label class="font-normal font-sans text-sm text-base-content">Grade Level</label>
                                                <div class="relative">
                                                    <input type="number" step="1" min="1" max="17" class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="grade-level" placeholder="Grade Level at Employment" value="" />
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-1/2 -translate-y-1/2 right-3 text-base-content/40">
                                                        <path d="M4 5m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"></path>
                                                        <path d="M4 15l16 0"></path>
                                                        <path d="M4 19l16 0"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                                            <div class="w-full">
                                                <label class="font-normal font-sans text-sm text-base-content">Ministry/Extra-Ministerial Department</label>
                                                <div class="relative">
                                                    <select class="w-full bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="ministerial-dept">
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
                                            <div class="w-full">
                                                <label class="font-normal font-sans text-sm text-base-content">Mode of Appointment</label>
                                                <div class="relative">
                                                    <select class="w-full bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="mode-of-employment">
                                                        <option value="">Select Option</option>
                                                        <option value="Permanent">Permanent</option>
                                                        <option value="Temporary">Temporary</option>
                                                        <option value="Contract">Contract</option>
                                                        <option value="Volunteer">Volunteer</option>
                                                        <option value="Other">Other</option>
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
                                            <div class="w-full">
                                                <label class="font-normal font-sans text-sm text-base-content">Address of Ministry/Extra-Ministerial Department</label>
                                                <div class="relative">
                                                    <textarea class="w-full pl-3 pr-10 py-2 bg-white placeholder:text-base-content/40 text-base-content/80 text-xs border border-base-300 rounded-md transition duration-300 ease focus:outline-none focus:border-primary hover:border-primary/50 focus:shadow" name="ministerial-dept-address" placeholder="Address of Ministry/Extra-Ministerial Department" rows="3"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="clonesContainer" class="flex flex-col items-start gap-x-2 w-full relative"></div>
                                </div>
                                <div class="py-3 px-4 flex flex-wrap items-center justify-end gap-2">
                                    <div class="flex items-center gap-2">
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
                    </div>
                </form>
            </div>
        </div>

        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
