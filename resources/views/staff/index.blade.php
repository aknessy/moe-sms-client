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
                        <li class="flex font-alkalami items-center text-lg/3 text-slate-500 transition-colors duration-300 cursor-not-allowed">
                            <a href="#">Profile Settings</a>
                        </li>
                    </ol>
                </nav>
                <div class="w-2/3 sm:w-full">
                    <h5 class="font-normal font-alkalami flex space-y-2 justify-end m-0 uppercase">Add Staff Record</h5>
                </div>
            </div>
            <div>
                <form type="post" accept-charset="" action="" class="lg:gap-x-8 gap-x-2 flex w-full py-4">
                    @csrf

                    <input type="hidden" name="form-type" value="basic-information" />

                    <div class="w-full flex flex-col lg:w-1/3">
                        <a href="#" class="flex items-center justify-start space-x-3 py-2 px-4 text-center text-slate-500 text-xs hover:text-slate-900 hover:underline transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                                <path d="M3 17l9 5l9 -5v-3l-9 5l-9 -5v-3l9 5l9 -5v-3l-9 5l-9 -5l9 -5l5.418 3.01"></path>
                            </svg>
                            <span class="">Upload Bulk</span>
                        </a>
                        <div class="w-full flex flex-col bg-brown-50 px-4 py-4 border border-brown-100 rounded-md">
                            <x-staff-sidebar activePage="{{ $active_page }}" />
                        </div>
                    </div>
                    <div class="w-full lg:w-2/3 px-4 py-2 bg-brown-50 border border-brown-100 rounded-lg">
                        <div class="flex flex-col items-start border-b border-b-brown-100">
                            <h2 class="text-lg font-alkalami font-semibold mb-0 text-slate-600">Basic Information</h2>
                            <p class="font-normal font-sans text-md text-slate-500 m-0">Create a new staff by filling their basic information</p>
                        </div>
                        <div class="py-4 block">
                            <div class="form-input-area py-3 px-3 mt-3 gap-y-4">
                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Establishment/Computer Number</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="computer-number" placeholder="Computer Number" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full max-w-sm">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Surname</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="last-name" placeholder="Surname" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-semibold font-sans text-sm text-slate-800">First name</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="first-name" placeholder="First Name" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Other names</label>
                                        <div class="relative">
                                            <input type="password" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="other-names" placeholder="Other Names" value />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Date of Birth</label>
                                        <div class="relative">
                                            <input type="date" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="date-of-birth" placeholder="YYYY/MM/DD" value="" />
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full max-w-sm">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Phone Number</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="phone-number" placeholder="Surname" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M3 3m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"></path>
                                                <path d="M8 4l2 0"></path>
                                                <path d="M9 17l0 .01"></path>
                                                <path d="M21 6l-2 3l2 3l-2 3l2 3"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Email</label>
                                        <div class="relative">
                                            <input type="email" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="phone-number" placeholder="Surname" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M3 9l9 6l9 -6l-9 -6l-9 6"></path>
                                                <path d="M21 9v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10"></path>
                                                <path d="M3 19l6 -6"></path>
                                                <path d="M15 13l6 6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-sm text-slate-800">State of Origin</label>
                                        <div class="relative">
                                            <select class="w-full bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="state-of-origin">
                                                <option value="brazil">Select Option</option>
                                            </select>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M9 18l3 3l3 -3"></path>
                                                <path d="M12 15v6"></path>
                                                <path d="M15 6l-3 -3l-3 3"></path>
                                                <path d="M12 3v6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Local Government of Origin</label>
                                        <div class="relative">
                                            <select class="w-full bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="lga-of-origin">
                                                <option value="brazil">Select Option</option>
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
                                    <div class="w-full max-w-sm">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Pension Fund Administrator</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="pfa" placeholder="PFA" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M9 21v-6a2 2 0 0 1 2 -2h1.341"></path>
                                                <path d="M19.682 10.682l-7.682 -7.682l-9 9h2v7a2 2 0 0 0 2 2h5"></path>
                                                <path d="M22 16c0 4 -2.5 6 -3.5 6s-3.5 -2 -3.5 -6c1 0 2.5 -.5 3.5 -1.5c1 1 2.5 1.5 3.5 1.5z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Retirement Savings Account No.</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="rsa" placeholder="Rsa" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M7 3m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z"></path>
                                                <path d="M17 17v2a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h2"></path>
                                                <path d="M15 14v-8l-4 6h5"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full">
                                        <label class="font-semibold font-sans text-sm text-slate-800">NHIS No.</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 focus:shadow" name="nhis" placeholder="NHIS" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M17 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                                <path d="M9 11a5 5 0 1 0 3.95 7.95"></path>
                                                <path d="M19 20l-4 -5h-4l3 -5l-4 -3l-4 1"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Permanent Home Address</label>
                                        <div class="relative">
                                            <textarea class="h-full min-h-[100px] w-full resize-none rounded-[7px] border border-slate-300 px-3 py-2.5 font-sans text-sm font-normal text-slate-700 outline outline-0 transition-all focus:border-1 focus:border-slate-400 focus:outline-0 disabled:resize-none disabled:border-0 disabled:bg-blue-gray-50" placeholder="Permanent Address"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="py-3 px-4 flex items-center justify-end gap-2">
                                    <button type="submit" class="rounded-md bg-gradient-to-tr from-slate-600 to-slate-700 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-600 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 hover:border-slate-800 active:border-slate-800 focus:border-slate-800 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                        Save & Continue
                                    </button>
                                    <button type="reset" class="rounded-md border border-brown-200 py-2 px-4 text-center text-sm transition-all shadow-sm hover:shadow-lg text-brown-600 hover:text-white hover:bg-brown-300 hover:border-gray-50 focus:text-white focus:bg-brown-300 focus:border-gray-50 active:border-gray-50 active:text-white active:bg-brown-300">
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
