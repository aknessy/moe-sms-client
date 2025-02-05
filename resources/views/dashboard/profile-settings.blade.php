<x-dashboard pageTitle="{{ $page_title }}">
    <x-slot name="slot">
        <div class="w-full flex flex-col bg-transparent">
            <div class="flex items-center justify-between">
                <nav aria-label="breadcrumb" class="w-1/3 space-y-2">
                    <ol class="flex w-full flex-wrap items-center rounded-md px-4 py-3">
                        <li class="flex cursor-pointer items-center text-lg/3 text-brown-600 transition-colors duration-300 hover:text-brown-700">
                            <a href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                </svg>
                            </a>
                            <span class="pointer-events-none mx-2 text-brown-600">/</span>
                        </li>
                        <li class="flex items-center text-lg/3 text-brown-500 transition-colors duration-300 cursor-not-allowed">
                            <a href="#">Profile Settings</a>
                        </li>
                    </ol>
                </nav>
                <div class="w-2/3 sm:w-full">
                    <h1 class="text-lg/3 font-semibold flex space-y-2 justify-end m-0">Staff Management - User Profile Settings</h1>
                </div>
            </div>
            <div class="lg:gap-x-8 gap-x-2 flex w-full py-4 flex-1">
                <div class="w-full flex flex-col lg:w-2/5 px-4 py-4 bg-brown-50 border border-brown-100 rounded-md">
                    <div class="flex flex-col items-center gap-y-2">
                        <div class="bg-white px-1 py-1 rounded-full h-36 w-36">
                            <img src="{{ Vite::asset('resources/images/avatars/avatar-2.png') }}" class="h-full w-full rounded-full" alt="Placeholder Image" />
                        </div>
                        <h3 class="font-bold font-sans text-2xl">Douglas Costa</h3>
                        <h4 class="font-normal text-lg">Ministry of Establishment</h4>
                        <h5 class="text-brown-900 font-normal text-sm">douglas.acosta@domain.com</h5>
                    </div>
                    <div class="mt-5 flex flex-col gap-y-2">
                        <a href="#" class="flex items-center justify-start space-x-3 rounded-lg bg-gradient-to-tr from-slate-600 to-slate-700 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-600 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 hover:border-slate-800 active:border-slate-800 focus:border-slate-800 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none peer">
                            <span class="rounded-full w-6 h-6 bg-brown-300 peer-hover:bg-slate-700 text-white peer-hover:text-brown-300 p-1 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                                  <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                                  <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
                                </svg>
                            </span>
                            <span class="">Personal Information</span>
                        </a>
                    </div>
                </div>
                <div class="w-full lg:w-3/5 px-4 py-2 bg-brown-50 border border-brown-100 rounded-lg">
                    <div class="flex flex-col items-start">
                        <h2 class="text-lg font-semibold mb-0">Personal Information</h2>
                        <p class="font-normal text-md text-slate-500 m-0">User's personal Information</p>
                    </div>
                    <div class="py-4 block">
                        <form type="post" accept-charset="" action="">
                            @csrf

                            <div class="lg:w-[400px] w-1/2">
                                <label class="font-semibold text-lg">Choose Avatar</label>
                                <div class="grid grid-cols-4 gap-2">
                                    @foreach($resources as $avatar)
                                        <div class="bg-transparent rounded-lg hover:bg-brown-50 p-2 cursor-pointer">
                                            <img src="{{ Vite::asset($avatar->url) }}" class="h-20 w-20 rounded-lg" alt="{{ $avatar->name }}" />
                                            <input type="hidden" name="res-avatar" value="{{ $avatar->id }}" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="form-input-area py-3 px-3 mt-3 border rounded-md border-brown-100 gap-y-4">
                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-md text-brown-900">Surname</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-brown-50 placeholder:text-brown-400 text-brown-600 text-sm border border-brown-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow" name="last-name" placeholder="Surname" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-brown-700">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-md text-brown-900">First name</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-brown-50 placeholder:text-brown-400 text-brown-600 text-sm border border-brown-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow" name="first-name" placeholder="First Name" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-brown-700">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-md text-brown-900">Other names</label>
                                        <div class="relative">
                                            <input type="password" class="w-full pl-3 pr-10 py-2 bg-brown-50 placeholder:text-brown-400 text-brown-600 text-sm border border-brown-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow" name="other-names" placeholder="Other Names" value />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-brown-700">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-md text-brown-900">Email</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-brown-50 placeholder:text-brown-400 text-brown-600 text-sm border border-brown-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow" name="email" placeholder="Email" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-brown-700">
                                                <path d="M8 9h8"></path>
                                                <path d="M8 13h6"></path>
                                                <path d="M13.5 19.5l-1.5 1.5l-3 -3h-3a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v6"></path>
                                                <path d="M22 22l-5 -5"></path>
                                                <path d="M17 22l5 -5"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-md text-brown-900">Phone</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-brown-50 placeholder:text-brown-400 text-brown-600 text-sm border border-brown-200 rounded-md transition duration-300 ease focus:outline-none focus:border-brown-300 hover:border-brown-300 shadow-sm focus:shadow" name="phone" placeholder="Phone number" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-brown-700">
                                                <path d="M3 3m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"></path>
                                                <path d="M8 4l2 0"></path>
                                                <path d="M9 17l0 .01"></path>
                                                <path d="M21 6l-2 3l2 3l-2 3l2 3"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-md text-brown-900">Department</label>
                                        <div class="relative">
                                            <select class="w-full bg-brown-50 placeholder:text-brown-400 text-slate-700 text-sm border border-brown-300 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-brown-300 hover:border-brown-400 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="department">
                                                <option value="brazil">Brazil</option>
                                                <option value="bucharest">Bucharest</option>
                                                <option value="london">London</option>
                                                <option value="washington">Washington</option>
                                            </select>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-brown-700">
                                                <path d="M9 18l3 3l3 -3"></path>
                                                <path d="M12 15v6"></path>
                                                <path d="M15 6l-3 -3l-3 3"></path>
                                                <path d="M12 3v6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-md text-brown-900">Unit</label>
                                        <div class="relative">
                                            <select class="w-full bg-brown-50 placeholder:text-brown-400 text-slate-700 text-sm border border-brown-300 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-brown-300 hover:border-brown-400 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="department">
                                                <option value="brazil">Brazil</option>
                                                <option value="bucharest">Bucharest</option>
                                                <option value="london">London</option>
                                                <option value="washington">Washington</option>
                                            </select>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-brown-700">
                                                <path d="M9 18l3 3l3 -3"></path>
                                                <path d="M12 15v6"></path>
                                                <path d="M15 6l-3 -3l-3 3"></path>
                                                <path d="M12 3v6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="py-3 px-4 flex items-center justify-end gap-2">
                                <button type="submit" class="rounded-md bg-gradient-to-tr from-slate-600 to-slate-700 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md hover:shadow-lg focus:bg-slate-600 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 hover:border-slate-800 active:border-slate-800 focus:border-slate-800 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                    Save Changes
                                </button>
                                <button type="reset" class="rounded-md border border-brown-200 py-2 px-4 text-center text-sm transition-all shadow-sm hover:shadow-lg text-brown-600 hover:text-white hover:bg-brown-300 hover:border-gray-50 focus:text-white focus:bg-brown-300 focus:border-gray-50 active:border-gray-50 active:text-white active:bg-brown-300">
                                    Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>
</x-dashboard>
