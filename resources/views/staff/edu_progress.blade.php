<x-dashboard pageTitle="{{ $page_title }}">
    <x-slot name="slot">
        <div class="w-full flex flex-col bg-transparent">
            <div class="flex items-center justify-between">
                <nav aria-label="breadcrumb" class="w-1/3 space-y-2">
                    <ol class="flex w-full flex-wrap items-center rounded-md px-4 py-3">
                        <li class="flex cursor-pointer items-center text-lg/3 text-slate-600 transition-colors duration-300 hover:text-brown-700">
                            <a href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                </svg>
                            </a>
                            <span class="pointer-events-none mx-2 text-slate-600">/</span>
                        </li>
                        <li class="flex items-center text-lg/3 text-slate-500 transition-colors duration-300 cursor-not-allowed">
                            <a href="#">Education</a>
                        </li>
                    </ol>
                </nav>
                <div class="w-2/3 sm:w-full">
                    <h5 class="font-semibold font-sans flex space-y-2 justify-end m-0 uppercase">Staff Management - Add New Entry</h5>
                </div>
            </div>
            <div>
                <form type="post" accept-charset="" action="" class="lg:gap-x-8 gap-x-2 flex w-full py-4">
                    @csrf

                    <input type="hidden" name="form-type" value="educational-progress" />

                    <div class="w-full flex flex-col lg:w-1/3">
                        <a href="#" class="flex items-center justify-start space-x-3 py-2 px-4 text-center text-slate-500 text-xs hover:text-slate-900 hover:underline transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                                <path d="M3 17l9 5l9 -5v-3l-9 5l-9 -5v-3l9 5l9 -5v-3l-9 5l-9 -5l9 -5l5.418 3.01"></path>
                            </svg>
                            <span class="">Upload Bulk</span>
                        </a>
                        <div class="w-full flex flex-col bg-brown-50 px-4 py-4 border border-brown-100 rounded-md">
                            <div class="mt-5 flex flex-col gap-y-3">
                                <a href="{{ route('profile-settings') }}" class="flex items-center justify-start space-x-3 rounded-lg bg-transparent py-2 px-4 text-center text-sm hover:text-slate-700 transition-all disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                    <span >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                                            <path d="M16 19h6"></path>
                                            <path d="M19 16v6"></path>
                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4"></path>
                                        </svg>
                                    </span>
                                    <span class="">Personal Information</span>
                                </a>
                                <a href="#" class="flex items-center justify-start space-x-3 rounded-lg bg-gradient-to-tr from-slate-600 to-slate-700 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md  focus:bg-slate-600 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 hover:border-slate-800 active:border-slate-800 focus:border-slate-800 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="rounded-full w-6 h-6 bg-brown-300 text-white  p-1 flex items-center justify-center">
                                        <path d="M5 4m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z"></path>
                                        <path d="M9 4m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z"></path>
                                        <path d="M5 8h4"></path>
                                        <path d="M9 16h4"></path>
                                        <path d="M13.803 4.56l2.184 -.53c.562 -.135 1.133 .19 1.282 .732l3.695 13.418a1.02 1.02 0 0 1 -.634 1.219l-.133 .041l-2.184 .53c-.562 .135 -1.133 -.19 -1.282 -.732l-3.695 -13.418a1.02 1.02 0 0 1 .634 -1.219l.133 -.041z"></path>
                                        <path d="M14 9l4 -1"></path>
                                        <path d="M16 16l3.923 -.98"></path>
                                    </svg>
                                    <span class="">Educational Progression</span>
                                </a>
                                <a href="#" class="flex items-center justify-start space-x-3 rounded-lg bg-transparent py-2 px-4 text-center text-sm hover:text-slate-700 transition-all disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                                        <path d="M6 4h11a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-11a1 1 0 0 1 -1 -1v-14a1 1 0 0 1 1 -1m3 0v18"></path>
                                        <path d="M13 8l2 0"></path>
                                        <path d="M13 12l2 0"></path>
                                    </svg>
                                    <span class="">Work/Job Progression</span>
                                </a>
                                <a href="#" class="flex items-center justify-start space-x-3 rounded-lg bg-transparent py-2 px-4 text-center text-sm hover:text-slate-700 transition-all disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                                        <path d="M13 12v.01"></path>
                                        <path d="M3 21h18"></path>
                                        <path d="M5 21v-16a2 2 0 0 1 2 -2h7.5m2.5 10.5v7.5"></path>
                                        <path d="M14 7h7m-3 -3l3 3l-3 3"></path>
                                    </svg>
                                    <span class="">Leave Record</span>
                                </a>
                                <a href="#" class="flex items-center justify-start space-x-3 rounded-lg bg-transparent py-2 px-4 text-center text-sm hover:text-slate-700 transition-all disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                                        <path d="M4 6l5.5 0"></path>
                                        <path d="M4 10l5.5 0"></path>
                                        <path d="M4 14l5.5 0"></path>
                                        <path d="M4 18l5.5 0"></path>
                                        <path d="M14.5 6l5.5 0"></path>
                                        <path d="M14.5 10l5.5 0"></path>
                                        <path d="M14.5 14l5.5 0"></path>
                                        <path d="M14.5 18l5.5 0"></path>
                                    </svg>
                                    <span class="">Referee(s)</span>
                                </a>
                                <a href="#" class="flex items-center justify-start space-x-3 rounded-lg bg-transparent py-2 px-4 text-center text-sm hover:text-slate-700 transition-all disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                                        <path d="M6 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-14a1 1 0 0 1 1 -1z"></path>
                                        <path d="M15 12l4 0"></path>
                                        <path d="M17 10l0 4"></path>
                                    </svg>
                                    <span class="">Next of Kin</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="w-full lg:w-2/3 px-4 py-2 bg-brown-50 border border-brown-100 rounded-lg">
                        <div class="flex flex-col items-start border-b border-b-brown-100">
                            <h2 class="text-lg font-semibold mb-0 text-slate-600">Education Progression</h2>
                            <p class="font-normal text-md text-slate-500 m-0">Staff Educational progress</p>
                        </div>
                        <div class="py-4 block">
                            <div class="form-input-area py-3 px-3 mt-3 gap-y-4">
                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full">
                                        <label class="font-sans text-sm text-slate-800">Institution Type</label>
                                        <div class="relative">
                                            <select class="w-full bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="institution-type[]">
                                                <option value="">Select Option</option>
                                                <option value="Tertiary">Tertiary</option>
                                                <option value="Secondary">Secondary</option>
                                                <option value="Primary">Primary</option>
                                                <option value="Others">Others</option>
                                            </select>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M9 18l3 3l3 -3"></path>
                                                <path d="M12 15v6"></path>
                                                <path d="M15 6l-3 -3l-3 3"></path>
                                                <path d="M12 3v6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full min-w-max">
                                        <label class="font-sans text-sm text-slate-800">Institution Name</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="institution-name[]" placeholder="Name of Institution" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M3 7v-2h13v2"></path>
                                                <path d="M10 5v14"></path>
                                                <path d="M12 19h-4"></path>
                                                <path d="M15 13v-1h6v1"></path>
                                                <path d="M18 12v7"></path>
                                                <path d="M17 19h2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full min-w-max">
                                        <label class="font-sans text-sm text-slate-800">From</label>
                                        <div class="relative">
                                            <input type="date" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="institution-start-date[]" placeholder="Start Date" value="" />
                                        </div>
                                    </div>
                                    <div class="w-full min-w-max">
                                        <label class="font-sans text-sm text-slate-800">To</label>
                                        <div class="relative">
                                            <input type="date" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="institution-end-date[]" placeholder="End Date" value="" />
                                        </div>
                                    </div>
                                </div>

                                <div class="py-3 px-4 flex items-center justify-between">
                                    <div>
                                        <button type="reset" class="add-institution rounded-md border border-slate-500 py-2 px-4 text-center text-sm transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-slate-400 hover:border-slate-600 focus:text-white focus:bg-slate-400 focus:border-slate-600 active:border-slate-600 active:text-white active:bg-slate-400">
                                            Add Institution
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="submit" class="rounded-md bg-gradient-to-tr from-slate-600 to-slate-700 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-600 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 hover:border-slate-800 active:border-slate-800 focus:border-slate-800 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                            Save Changes
                                        </button>
                                        <button type="reset" class="rounded-md border border-brown-200 py-2 px-4 text-center text-sm transition-all shadow-sm hover:shadow-lg text-brown-600 hover:text-white hover:bg-brown-300 hover:border-gray-50 focus:text-white focus:bg-brown-300 focus:border-gray-50 active:border-gray-50 active:text-white active:bg-brown-300">
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
<script>
    $(function(){

    })
</script>
