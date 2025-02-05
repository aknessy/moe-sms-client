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
                        <li class="flex text-slate-500 items-center transition-colors duration-300 cursor-not-allowed">
                            <a href="#">Profile Settings</a>
                        </li>
                    </ol>
                </nav>
                <div class="w-2/3 sm:w-full">
                    <h5 class="font-semibold font-sans flex space-y-2 justify-end m-0">Staff Management - User Profile Settings</h5>
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
                        <h5 class="text-slate-600 font-normal text-sm">douglas.acosta@domain.com</h5>
                    </div>
                    <div class="mt-5 flex flex-col gap-y-3">
                        <a href="{{ route('profile-settings') }}" class="flex items-center justify-start space-x-3 rounded-lg bg-gradient-to-tr from-slate-600 to-slate-700 py-2 px-4 border border-transparent text-center text-sm text-white transition-all shadow-md  focus:bg-slate-600 focus:shadow-none active:bg-slate-700 hover:bg-slate-700 hover:border-slate-800 active:border-slate-800 focus:border-slate-800 active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                            <span class="rounded-full w-6 h-6 bg-brown-300 text-white  p-1 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                                  <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                                  <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
                                </svg>
                            </span>
                            <span class="">Personal Information</span>
                        </a>
                        <a href="{{ route('change-password') }}" class="flex items-center justify-start space-x-3 rounded-lg bg-transparent py-2 px-4 text-center text-sm hover:text-slate-700 transition-al disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                            <svg  xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2c5.523 0 10 4.477 10 10a10 10 0 0 1 -20 0c0 -5.523 4.477 -10 10 -10zm2 5a3 3 0 0 0 -2.98 2.65l-.015 .174l-.005 .176l.005 .176c.019 .319 .087 .624 .197 .908l.09 .209l-3.5 3.5l-.082 .094a1 1 0 0 0 0 1.226l.083 .094l1.5 1.5l.094 .083a1 1 0 0 0 1.226 0l.094 -.083l.083 -.094a1 1 0 0 0 0 -1.226l-.083 -.094l-.792 -.793l.585 -.585l.793 .792l.094 .083a1 1 0 0 0 1.403 -1.403l-.083 -.094l-.792 -.793l.792 -.792a3 3 0 1 0 1.293 -5.708zm0 2a1 1 0 1 1 0 2a1 1 0 0 1 0 -2z"></path>
                            </svg>
                            <span class="">Password</span>
                        </a>
                    </div>
                </div>
                <div class="w-full lg:w-3/5 px-4 py-2 bg-brown-50 border border-brown-100 rounded-lg">
                    <div class="flex flex-col items-start">
                        <h2 class="text-lg font-semibold text-slate-600 mb-0">Personal Information</h2>
                        <p class="font-normal text-md text-slate-500 m-0">User's personal Information</p>
                    </div>
                    <div class="py-4 block">
                        <form type="post" accept-charset="" action="">
                            @csrf

                            <div class="lg:w-[400px] w-1/2">
                                <label class="font-semibold text-lg">Choose Avatar</label>
                                <div class="grid grid-cols-4 gap-2">
                                    @foreach($resources as $avatar)
                                        <div class="bg-transparent rounded-lg hover:bg-brown-100 p-2 cursor-pointer">
                                            <img src="{{ Vite::asset($avatar->url) }}" class="h-20 w-20 rounded-lg" alt="{{ $avatar->name }}" />
                                            <input type="hidden" name="res-avatar" value="{{ $avatar->id }}" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="form-input-area py-3 px-3 mt-3 border rounded-md border-brown-100 gap-y-4">
                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full max-w-sm">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Surname</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow" name="last-name" placeholder="Surname" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-semibold font-sans text-sm text-slate-800">First name</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow" name="first-name" placeholder="First Name" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Other names</label>
                                        <div class="relative">
                                            <input type="password" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow" name="other-names" placeholder="Other Names" value />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 w-full lg:flex lg:space-x-4 space-x-2">
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Email</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow" name="email" placeholder="Email" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
                                                <path d="M8 9h8"></path>
                                                <path d="M8 13h6"></path>
                                                <path d="M13.5 19.5l-1.5 1.5l-3 -3h-3a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v6"></path>
                                                <path d="M22 22l-5 -5"></path>
                                                <path d="M17 22l5 -5"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-sm min-w-[200px]">
                                        <label class="font-semibold font-sans text-sm text-slate-800">Phone</label>
                                        <div class="relative">
                                            <input type="text" class="w-full pl-3 pr-10 py-2 bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded-md transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow" name="phone" placeholder="Phone number" value="" />
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2" class="absolute w-5 h-5 top-2.5 right-2.5 text-slate-400">
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
                                        <label class="font-semibold font-sans text-sm text-slate-800">Department</label>
                                        <div class="relative">
                                            <select class="w-full bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="department">
                                                <option value="brazil">Brazil</option>
                                                <option value="bucharest">Bucharest</option>
                                                <option value="london">London</option>
                                                <option value="washington">Washington</option>
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
                                        <label class="font-semibold font-sans text-sm text-slate-800">Unit</label>
                                        <div class="relative">
                                            <select class="w-full bg-gray-50 placeholder:text-slate-400 text-slate-600 text-sm border border-gray-200 rounded pl-3 pr-8 py-2 transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow-md appearance-none cursor-pointer" name="unit">
                                                <option value="brazil">Brazil</option>
                                                <option value="bucharest">Bucharest</option>
                                                <option value="london">London</option>
                                                <option value="washington">Washington</option>
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
                                <div class="w-full">
                                    <p class="font-semibold font-sans text-sm text-slate-800">Gender</p>
                                    <div class="flex gap-10">
                                        <div class="inline-flex items-center">
                                            <label
                                                class="relative flex cursor-pointer items-center rounded-full p-3"
                                                for="on"
                                                data-ripple-dark="true"
                                            >
                                                <input name="gender" value="male"
                                                type="radio"
                                                class="before:content[''] peer h-5 w-5 cursor-pointer appearance-none rounded-full border border-slate-300 checked:border-slate-400 transition-all before:absolute before:top-2/4 before:left-2/4 before:block before:h-12 before:w-12 before:-translate-y-2/4 before:-translate-x-2/4 before:rounded-full before:bg-slate-400 before:opacity-0 before:transition-opacity hover:before:opacity-10"
                                                id="on"/>
                                                <span class="absolute bg-slate-800 w-3 h-3 rounded-full opacity-0 peer-checked:opacity-100 transition-opacity duration-200 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"></span>
                                            </label>
                                            <label
                                                class="text-slate-600 cursor-pointer text-sm"
                                                for="on"
                                            >
                                            Female
                                        </label>
                                        </div>
                                        <div class="inline-flex items-center">
                                            <label
                                                class="relative flex cursor-pointer items-center rounded-full p-3"
                                                for="on"
                                                data-ripple-dark="true"
                                                >
                                                    <input name="gender" value="male"
                                                    type="radio"
                                                    class="before:content[''] peer h-5 w-5 cursor-pointer appearance-none rounded-full border border-slate-300 checked:border-slate-400 transition-all before:absolute before:top-2/4 before:left-2/4 before:block before:h-12 before:w-12 before:-translate-y-2/4 before:-translate-x-2/4 before:rounded-full before:bg-slate-400 before:opacity-0 before:transition-opacity hover:before:opacity-10"
                                                    id="on"
                                                    />
                                                    <span class="absolute bg-slate-800 w-3 h-3 rounded-full opacity-0 peer-checked:opacity-100 transition-opacity duration-200 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"></span>
                                                </label>
                                                <label
                                                    class="text-slate-600 cursor-pointer text-sm"
                                                    for="on"
                                                >
                                                Male
                                            </label>
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
        <x-footer appVersion="{{ $app_version }}"/>
    </x-slot>
</x-dashboard>
